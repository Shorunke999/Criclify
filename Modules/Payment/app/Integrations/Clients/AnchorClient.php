<?php
namespace Modules\Payment\Integrations\Clients;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

class AnchorClient
{
    protected string $baseUrl;
    protected string $secretKey;

    public function __construct()
    {
        $this->baseUrl = config('payment.anchor.base_url');
        $this->secretKey = config('payment.anchor.secret_key');
    }

    protected function headers(): array
    {
        return [
            'x-anchor-key' => $this->secretKey,
            'Content-Type' => 'application/json',
        ];
    }

    public function createCustomer(array $payload): array
    {
        return Http::withHeaders($this->headers())
            ->post($this->baseUrl . '/api/v1/customers', $payload)
            ->json();
    }

    public function verifyKyc(array $payload): array
    {
        return Http::withHeaders($this->headers())
            ->post($this->baseUrl . '/api/v1/customers', $payload)
            ->json();
    }
    public function createDepositAccount(array $payload): array
    {
        return Http::withHeaders($this->headers())
            ->post($this->baseUrl . '/api/v1/accounts', $payload)
            ->json();
    }
    public function createAccount(array $payload): array
    {
        return Http::withHeaders($this->headers())
            ->get($this->baseUrl . '/api/v1/account-numbers', $payload)
            ->json();
    }
    public function verifyWebhookSignature(Request $request):bool
    {
        $jsonData = file_get_contents('php://input');
        $headers = $request->headers();
        foreach ($headers as $name => $value)
        {
            if ($name == 'x-anchor-signature')
            {
                $anchorSignature = $value;
                break;
            }
        }
        $secretKey = $this->secretKey; // Secret key

        // Perform HMAC-SHA1 encryption and encode to base64
        $hmacSha1Hash = hash_hmac('sha1', $jsonData, $secretKey, false);

        //base64 encode the hash
        $base64EncodedHash = base64_encode($hmacSha1Hash);

        //Check if the encoded signature matches that in the webhook request header
        if ($anchorSignature != $base64EncodedHash)
        {
            return false;
        }
        return true;
    }
}
