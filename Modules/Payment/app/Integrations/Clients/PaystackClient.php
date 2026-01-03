<?php
namespace Modules\Payment\Integrations\Clients;

use Illuminate\Support\Facades\Http;

class PaystackClient
{
    protected string $baseUrl;
    protected string $secretKey;

    public function __construct()
    {
        $this->baseUrl = config('payment.paystack.base_url');
        $this->secretKey = config('payment.paystack.secret_key');
    }

    protected function headers(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->secretKey,
            'Content-Type' => 'application/json',
        ];
    }

    public function initialize(array $payload): array
    {
        return Http::withHeaders($this->headers())
            ->post($this->baseUrl . '/transaction/initialize', $payload)
            ->json();
    }

    public function verify(string $reference): array
    {
        return Http::withHeaders($this->headers())
            ->get($this->baseUrl . "/transaction/verify/{$reference}")
            ->json();
    }
}
