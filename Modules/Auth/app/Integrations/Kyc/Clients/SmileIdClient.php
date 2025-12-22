<?php

namespace Modules\Auth\Integrations\Kyc\Clients;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;

class SmileIdClient
{
    protected string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('kyc.smileid.sandbox')
            ? 'https://testapi.smileidentity.com/v1/'
            : 'https://api.smileidentity.com/v1/';
    }

    public function createJob(array $payload):Response
    {
        return Http::timeout(30)
            ->withHeaders($this->defaultHeaders())
            ->post($this->baseUrl . 'upload', $payload);
    }

    public function uploadZip(string $uploadUrl, string $zipContent):Response
    {
        return Http::timeout(60)
            ->withHeaders([
                'Content-Type' => 'application/zip',
            ])
            ->withBody($zipContent, 'application/zip')
            ->put($uploadUrl);
    }

    protected function defaultHeaders(): array
    {
        return [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
    }
}
