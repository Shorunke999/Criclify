<?php
namespace Modules\Auth\Integrations\Kyc\DTO;

class KycResponseDto
{
    public function __construct(
        public string $userId,
        public string $jobId,
        public string $smileJobId,
        public string $resultCode,
        public string $resultText,
        public array $actions,
        public ?array $personalInfo = null,
        public ?string $documentImage = null,
        public bool $isPassed = false
    ) {}

    public static function fromCallback(array $data): self
    {
        return new self(
            userId: $data['PartnerParams']['user_id'] ?? '',
            jobId: $data['PartnerParams']['job_id'] ?? '',
            smileJobId: $data['SmileJobID'] ?? '',
            resultCode: $data['ResultCode'] ?? '',
            resultText: $data['ResultText'] ?? '',
            actions: $data['Actions'] ?? [],
            personalInfo: [
                'full_name' => $data['FullName'] ?? null,
                'dob' => $data['DOB'] ?? null,
                'id_number' => $data['IDNumber'] ?? null,
                'gender' => $data['Gender'] ?? null,
                'expiration_date' => $data['ExpirationDate'] ?? null,
            ],
            documentImage: $data['Document'] ?? null,
            isPassed: in_array($data['ResultCode'] ?? '', ['0810', '0811'])
        );
    }
}
