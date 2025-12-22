<?php
namespace Modules\Auth\Integrations\Kyc\Helpers;

class SignatureHelper
{
    public static function generate(string $timestamp, string $partnerId, string $apiKey): string
    {
        $data = $timestamp . $partnerId . 'sid_request';
        return base64_encode(hash_hmac('sha256', $data, $apiKey, true));
    }

    public static function verify(string $signature, string $timestamp, string $partnerId, string $apiKey): bool
    {
        $expectedSignature = self::generate($timestamp, $partnerId, $apiKey);
        return hash_equals($expectedSignature, $signature);
    }
}
