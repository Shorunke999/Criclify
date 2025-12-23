<?php

namespace Modules\Auth\Services;

use App\Enums\KycProvider;
use App\Trait\ResponseTrait as TraitResponseTrait;
use App\Traits\ResponseTrait;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Modules\Auth\Integrations\Kyc\Services\SmileIdService;
use Modules\Auth\Integrations\Kyc\DTO\KycRequestDto;
use Modules\Auth\Models\KycVerification;

class KycService
{

    use ResponseTrait;
    protected $provider;
    public function __construct(

    ) {
         $this->provider = config('kyc.provider');
    }

    public function submitVerification(array $validated): JsonResponse
    {

        $user = auth()->user();
        $jobId = 'job_' . Str::uuid();

        try {
            $dto = new KycRequestDto(
                userId: (string) $user->id,
                jobId: $jobId,
                country: $validated['country'],
                idType: $validated['id_type'],
                selfieImage: $validated['selfie_image'],
                idCardImage: $validated['id_card_image'],
                idCardBackImage: $validated['id_card_back_image'] ?? null
            );
            if($this->provider == KycProvider::SMILEID){
                $smileIdService = new SmileIdService();
                $result = $smileIdService->verifyDocument($dto);
                $user->kyc_status =  $result->status;
                $data = [
                    'verification_id' => $result->id,
                    'job_id' => $jobId,
                    'status' => $result->status,
                ];
            }

            return $this->success_response($data,'Kyc Verification Submitted Successfully.',200);

        } catch (Exception $e) {
             $this->reportError($e,"Auth",[
                'action' => 'submitVerification',
                'service' => 'kycService'
            ]);
            return $this->error_response('KYC verification failed: '.$e->getMessage(),$e->getCode() ?: 500);
        }
    }

    public function handleCallback(Request $request): JsonResponse
    {
        try {
            $callbackData = $request->all();
            if($this->provider == KycProvider::SMILEID){
                $smileIdService = new SmileIdService();
                $smileIdService->handleCallback($callbackData);
            }
             return $this->success_response(['success' => true], 200);

        } catch (Exception $e) {
             $this->reportError($e,"Auth",[
                'action' => 'handleCallback',
                'service' => 'kycService'
            ]);
            return $this->error_response('Callback processing failed.', $e->getCode() ?: 400);
        }
    }

    public function getVerificationStatus()
    {
        try{
            $user = Auth::user();

            $verification = KycVerification::where('user_id', $user->id)
                ->latest()
                ->first();

            if (!$verification) {
                throw new Exception('Kyc Not submited',404);
            }
            $data = [
                    'verification_status' => $verification->status,
                    'result_text' => $verification->result_text,
                    'submitted_at' => $verification->created_at,
                    'verified_at' => $verification->verified_at,
            ];
            return $this->success_response($data,
            'Kyc Fetched Successfully'
            );
        }catch(Exception $e)
        {
             $this->reportError($e,"Auth",[
                'action' => 'getVerification',
                'service' => 'kycService'
            ]);
            return $this->error_response('Unable to fetch Verify kyc status:', $e->getCode() ?: 400);
        }
    }
}
