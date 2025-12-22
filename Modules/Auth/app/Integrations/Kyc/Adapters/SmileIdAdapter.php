<?php

namespace Modules\Auth\Integrations\Kyc\Adapters;

use Modules\Auth\Integrations\Kyc\DTO\KycRequestDto;
use Modules\Auth\Integrations\Kyc\Helpers\SignatureHelper;

class SmileIdAdapter
{
    public function createJobPayload(KycRequestDto $dto): array
    {
        $timestamp = now()->toIso8601String();
        $partnerId = config('kyc.smileid.partner_id');
        $apiKey = config('kyc.smileid.api_key');

        return [
            'callback_url' => $dto->callbackUrl ?? config('kyc.smileid.callback_url'),
            'model_parameters' => new \stdClass(),
            'partner_params' => [
                'job_id' => $dto->jobId,
                'job_type' => 6,
                'user_id' => $dto->userId,
            ],
            'signature' => SignatureHelper::generate($timestamp, $partnerId, $apiKey),
            'smile_client_id' => $partnerId,
            'source_sdk' => 'rest_api',
            'source_sdk_version' => '1.0.0',
            'timestamp' => $timestamp,
        ];
    }

    public function createInfoJson(KycRequestDto $dto): array
    {
        $images = [];

        // Add selfie (image_type_id: 2 for base64)
        if ($this->isBase64($dto->selfieImage)) {
            $images[] = [
                'image_type_id' => 2,
                'image' => $dto->selfieImage,
                'file_name' => '',
            ];
        } else {
            $images[] = [
                'image_type_id' => 0,
                'image' => '',
                'file_name' => 'selfie.jpg',
            ];
        }

        // Add ID card front (image_type_id: 3 for base64)
        if ($this->isBase64($dto->idCardImage)) {
            $images[] = [
                'image_type_id' => 3,
                'image' => $dto->idCardImage,
                'file_name' => '',
            ];
        } else {
            $images[] = [
                'image_type_id' => 1,
                'image' => '',
                'file_name' => 'id_card.jpg',
            ];
        }

        // Add ID card back if provided
        if ($dto->idCardBackImage) {
            if ($this->isBase64($dto->idCardBackImage)) {
                $images[] = [
                    'image_type_id' => 7,
                    'image' => $dto->idCardBackImage,
                    'file_name' => '',
                ];
            } else {
                $images[] = [
                    'image_type_id' => 5,
                    'image' => '',
                    'file_name' => 'id_card_back.jpg',
                ];
            }
        }

        return [
            'package_information' => [
                'apiVersion' => [
                    'buildNumber' => 0,
                    'majorVersion' => 2,
                    'minorVersion' => 0,
                ],
            ],
            'id_info' => [
                'country' => $dto->country,
                'id_type' => $dto->idType,
            ],
            'images' => $images,
        ];
    }

    public function isBase64(string $string): bool
    {
        return base64_encode(base64_decode($string, true)) === $string;
    }
}
