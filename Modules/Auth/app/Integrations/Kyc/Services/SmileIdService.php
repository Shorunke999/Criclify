<?php
namespace Modules\Auth\Integrations\Kyc\Services;

use App\Enums\AuditAction;
use Exception;
use App\Enums\KycStatus;
use ZipArchive;
use Modules\Auth\Integrations\Kyc\Clients\SmileIdClient;
use Modules\Auth\Integrations\Kyc\Adapters\SmileIdAdapter;
use Modules\Auth\Integrations\Kyc\DTO\KycRequestDto;
use Modules\Auth\Integrations\Kyc\DTO\KycResponseDto;
use Modules\Auth\Integrations\Kyc\Helpers\SignatureHelper;
use Modules\Auth\Models\KycVerification;
use Modules\Core\Events\AuditLogged;

class SmileIdService
{
    protected $client;
    protected $adapter;
    public function __construct(
    ) {
         $this->client = new  SmileIdClient();
         $this->adapter = new  SmileIdAdapter();
    }

    public function verifyDocument(KycRequestDto $dto)
    {
        try {
            // Step 1: Create job
            $jobPayload = $this->adapter->createJobPayload($dto);
            $jobResponse = $this->client->createJob($jobPayload);

            if ($jobResponse->failed()) {
                throw new Exception('Job creation failed: ' . $jobResponse->body());
            }

            $jobData = $jobResponse->json();
            $uploadUrl = $jobData['upload_url'];

            // Step 2: Create and upload zip file
            $zipPath = $this->createZipFile($dto);
            $uploadResponse = $this->client->uploadZip($uploadUrl, file_get_contents($zipPath));

            // Clean up temp file
            unlink($zipPath);

            if ($uploadResponse->failed()) {
                throw new Exception('Upload failed: ' . $uploadResponse->body());
            }

            return KycVerification::create([
                'user_id' => $dto->userId,
                'job_id' => $dto->jobId,
                'smile_job_id' => $jobData['smile_job_id'],
                'country' => $dto->country,
                'id_type' => $jobData['id_type'],
                'status' => KycStatus::PENDING,
            ]);

        } catch (Exception $e) {
            //log error in rusnag
            throw $e;
        }
    }

    protected function createZipFile(KycRequestDto $dto): string
    {
        $zipPath = storage_path('app/temp/' . $dto->jobId . '.zip');

        // Ensure temp directory exists
        if (!file_exists(dirname($zipPath))) {
            mkdir(dirname($zipPath), 0755, true);
        }

        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new Exception('Failed to create zip file');
        }

        // Add info.json
        $infoJson = $this->adapter->createInfoJson($dto);
        $zip->addFromString('info.json', json_encode($infoJson));

        // Add image files if not base64
        if (!$this->adapter->isBase64($dto->selfieImage)) {
            $zip->addFile($dto->selfieImage, 'selfie.jpg');
        }

        if (!$this->adapter->isBase64($dto->idCardImage)) {
            $zip->addFile($dto->idCardImage, 'id_card.jpg');
        }

        if ($dto->idCardBackImage && !$this->adapter->isBase64($dto->idCardBackImage)) {
            $zip->addFile($dto->idCardBackImage, 'id_card_back.jpg');
        }

        $zip->close();

        return $zipPath;
    }

    public function handleCallback(array $callbackData)
    {
        // Verify signature
        $signature = $callbackData['signature'] ?? '';
        $timestamp = $callbackData['timestamp'] ?? '';
        $partnerId = config('kyc.smileid.partner_id');
        $apiKey = config('kyc.smileid.api_key');

        if (!SignatureHelper::verify($signature, $timestamp, $partnerId, $apiKey)) {
            throw new Exception('Invalid callback signature');
        }

        $adapter = KycResponseDto::fromCallback($callbackData);

        $verification = KycVerification::where('job_id', $adapter->jobId)->first();

        if (!$verification) {
            throw new Exception('Verification not found',404);
        }
        $status = $adapter->isPassed ? KycStatus::VERIFIED->value : KycStatus::FAILED->value;
        $verification->update([
            'smile_job_id' => $adapter->smileJobId,
            'status' => $status,
            'result_code' => $adapter->resultCode,
            'result_text' => $adapter->resultText,
            'actions' => $adapter->actions,
            'personal_info' => $adapter->personalInfo,
            'document_image' => $adapter->documentImage,
        ]);

        // Update user's KYC status
        $verification->user->update([
            'kyc_status' => $status,
            'kyc_verified_at' => $adapter->isPassed ? now() : null,
        ]);

        event(new AuditLogged(
                userId: $adapter->userId,
                action:  $adapter->isPassed ? AuditAction::KYC_APPROVED->value : AuditAction::KYC_REJECTED->value,
                entityType: 'KycVerification',
                entityId: $verification->id,
                metadata: [
                    'kyc_provider' => 'SmileID',
                ],
                version: null
            ));
        return;
    }
}
