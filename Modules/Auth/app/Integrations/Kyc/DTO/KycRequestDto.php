<?php
namespace Modules\Auth\Integrations\Kyc\DTO;

class KycRequestDto
{
    public function __construct(
        public string $userId,
        public string $jobId,
        public string $country,
        public string $idType,
        public string $selfieImage, // base64 or file path
        public string $idCardImage, // base64 or file path
        public ?string $idCardBackImage = null, // optional back image
        public ?string $callbackUrl = null
    ) {}

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'job_id' => $this->jobId,
            'country' => $this->country,
            'id_type' => $this->idType,
            'selfie_image' => $this->selfieImage,
            'id_card_image' => $this->idCardImage,
            'id_card_back_image' => $this->idCardBackImage,
            'callback_url' => $this->callbackUrl,
        ];
    }
}
