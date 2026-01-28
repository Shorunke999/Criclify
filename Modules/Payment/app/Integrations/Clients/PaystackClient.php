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

    public function createCustomer(array $payload): array
    {
        return Http::withHeaders($this->headers())
            ->post($this->baseUrl . '/customer', $payload)
            ->json();
    }

    public function createDepositAccount(array $payload): array
    {
        return Http::withHeaders($this->headers())
            ->post($this->baseUrl . '/dedicated_account', $payload)
            ->json();
    }
    public function listBanks(array $params = [])
    {
        return Http::withHeaders($this->headers())
            ->get($this->baseUrl . '/bank', $params)
            ->json();
    }
      public function resolveAccount(array $payload): array
    {
        return Http::withHeaders($this->headers())
            ->post($this->baseUrl . '/bank/resolve', $payload)
            ->json();
    }
    public function createRecipient(array $payload)
    {
        return Http::withHeaders($this->headers())
            ->post($this->baseUrl . '/transferrecipient', $payload)
            ->json();
    }
    public function initiateTransfer(array $payload): array
    {
        return Http::withHeaders($this->headers())
            ->post($this->baseUrl . '/transfer', $payload)
            ->json();
    }
    public function validateCustomer(array $payload,$customer_code)
    {
        return Http::withHeaders($this->headers())
            ->post($this->baseUrl . '/'.$customer_code.'/identification', $payload)
            ->json();
    }
}
