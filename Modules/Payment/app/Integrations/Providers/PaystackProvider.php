<?php
namespace Modules\Payment\Integrations\Providers;

use Illuminate\Http\Request;
use Modules\Payment\Integrations\Clients\PaystackClient;
use Modules\Payment\Repositories\Contracts\PaymentProviderInterface;

class PaystackProvider implements PaymentProviderInterface
{
    public function __construct(
        protected PaystackClient $client
    ) {}

    public function initialize(array $payload): array
    {
        $response = $this->client->initialize([
            'email' => auth()->user()->email,
            'amount' => (int) ($payload['amount'] * 100),
            'reference' => $payload['reference'],
            'callback_url' => $payload['callback_url'],
            'channels' => ['card'],
            'metadata' => $payload['metadata'] ?? [],
        ]);

        if (! ($response['status'] ?? false)) {
            throw new \Exception('Paystack initialization failed: ' . ($response['message'] ?? 'Unknown error'));
        }

        return [
            'authorization_url' => $response['data']['authorization_url'],
            'reference' => $response['data']['reference'],
            'provider' => 'paystack',
        ];
    }

    public function verify(string $reference): array
    {
        $response = $this->client->verify($reference);

        if (! ($response['status'] ?? false)) {
            return ['status' => 'failed'];
        }

        return [
            'status' => $response['data']['status'], // success | failed
            'amount' => $response['data']['amount'] / 100,
            'reference' => $reference,
            'raw' => $response,
        ];
    }
    public function webhook()
    {
        $request = request();
        if (! $this->isValidSignature($request)) {
            throw new \Exception('Invalid Paystack webhook signature',401);
        }

        if ($request->event !== 'charge.success') {
            throw new \Exception('Unhandled Paystack webhook event: ' . $request->event);
        }
        $reference = $request->data['reference'];


        return $reference;
    }
    protected function isValidSignature(Request $request): bool
    {
        $signature = $request->header('x-paystack-signature');

        $expected = hash_hmac(
            'sha512',
            $request->getContent(),
            config('payment.paystack.secret_key')
        );

        return hash_equals($expected, $signature);
    }
}
