<?php

namespace Modules\Payment\Integrations\Service;

use App\Enums\KycStatus;
use Exception;
use Modules\Payment\Integrations\Clients\StripeClient;
use Modules\Payment\Managers\Contracts\BankContract;
use Modules\Payment\Managers\Dtos\Bank\AccountDto;
use Modules\Payment\Managers\Dtos\Bank\AccountVerificationDto;
use Modules\Payment\Managers\Dtos\Bank\BankDto;
use Modules\Payment\Managers\Dtos\Bank\CreateCustomerDto;
use Modules\Payment\Managers\Dtos\Bank\TransferDto;
use Modules\Payment\Managers\Dtos\Bank\TransferRecipientDto;

class StripeService implements BankContract
{
    protected $client;

    public function __construct()
    {
        $this->client = new StripeClient();
    }

    public function createCustomer(array $data): CreateCustomerDto
    {
        $user = auth()->user();

        $payload = [
            'email' => $user->email,
            'name' => ($data['first_name'] ?? $user->first_name) . ' ' . ($data['last_name'] ?? $user->last_name),
            'phone' => $data['phone_number'] ?? $user->meta->phone_number ?? null,
            'metadata' => [
                'user_id' => $user->id,
                'first_name' => $data['first_name'] ?? $user->first_name,
                'last_name' => $data['last_name'] ?? $user->last_name,
            ],
        ];

        $customer = $this->client->createCustomer($payload);

        return new CreateCustomerDto(
            firstName: $data['first_name'] ?? $user->first_name,
            lastName: $data['last_name'] ?? $user->last_name,
            email: $user->email,
            phoneNumber: $data['phone_number'] ?? $user->meta->phone_number ?? '',
            customerId: $customer->id,
            provider: 'stripe',
            kycVerified: false
        );
    }

    public function verifyKyc(CreateCustomerDto $dataObj): KycStatus
    {
        // Stripe uses Stripe Identity for KYC
        // For now, return pending
        return KycStatus::PENDING;
    }

    public function createDepositAccount(CreateCustomerDto $dataObj): AccountDto
    {
        // Stripe doesn't have "deposit accounts" like Paystack DVA
        // Instead, customers add payment methods (cards, bank accounts)
        // Return a virtual account representation

        return new AccountDto(
            providerAcctId: $dataObj->customerId,
            providerAcctNumber: null, // No account number in Stripe
            providerAcctName: $dataObj->firstName . ' ' . $dataObj->lastName,
            provider: 'stripe',
            bankName: 'Stripe',
            currency: config('payment.stripe.currency', 'usd')
        );
    }

    public function createAccount(AccountDto $dataObj): AccountDto
    {
        // Not needed for Stripe
        return $dataObj;
    }

    public function listBanks(string $country = 'US'): array
    {
        // Stripe doesn't have a bank list API
        // Return common US banks or empty array
        throw new Exception('Bank listing not supported by Stripe');
    }

    public function resolveAccountNumber(string $accountNumber, string $bankCode): AccountVerificationDto
    {
        throw new Exception('Account resolution not supported by Stripe');
    }

    public function createTransferRecipient(array $data): TransferRecipientDto
    {
        // In Stripe, this is creating a bank account for payouts
        $customerId = $data['customer_id'];

        $bankAccountData = [
            'source' => [
                'object' => 'bank_account',
                'country' => $data['country'] ?? 'US',
                'currency' => $data['currency'] ?? 'usd',
                'account_number' => $data['account_number'],
                'routing_number' => $data['routing_number'], // US routing number
                'account_holder_name' => $data['account_name'],
                'account_holder_type' => $data['account_type'] ?? 'individual', // 'individual' or 'company'
            ],
        ];

        $bankAccount = $this->client->createBankAccount($customerId, $bankAccountData);

        return new TransferRecipientDto(
            recipientCode: $bankAccount->id,
            recipientName: $data['account_name'],
            accountNumber: $data['account_number'],
            bankCode: $data['routing_number'],
            bankName: $bankAccount->bank_name ?? 'Unknown',
            provider: 'stripe'
        );
    }

    public function initiateTransfer(array $data): TransferDto
    {
        // Stripe payouts
        $payload = [
            'amount' => (int)($data['amount'] * 100), // Convert to cents
            'currency' => $data['currency'] ?? config('payment.stripe.currency', 'usd'),
            'destination' => $data['recipient_code'], // bank account ID
            'method' => 'standard', // or 'instant'
            'metadata' => [
                'reference' => $data['reference'] ?? uniqid('stripe_'),
                'reason' => $data['reason'] ?? 'Wallet withdrawal',
            ],
        ];

        $payout = $this->client->createPayout($payload);

        return new TransferDto(
            transferCode: $payout->id,
            amount: $payout->amount / 100,
            status: $this->mapStripeStatus($payout->status),
            reference: $data['reference'] ?? $payout->id,
            recipientCode: $data['recipient_code'],
            provider: 'stripe'
        );
    }

    public function verifyTransfer(string $reference): TransferDto
    {
        // Retrieve payout status
        $payout = $this->client->stripe->payouts->retrieve($reference);

        return new TransferDto(
            transferCode: $payout->id,
            amount: $payout->amount / 100,
            status: $this->mapStripeStatus($payout->status),
            reference: $reference,
            recipientCode: $payout->destination,
            provider: 'stripe'
        );
    }

    public function getBalance(): array
    {
        $balance = $this->client->stripe->balance->retrieve();

        // Stripe returns balance in cents
        $availableBalance = 0;
        foreach ($balance->available as $item) {
            if ($item->currency === config('payment.stripe.currency', 'usd')) {
                $availableBalance = $item->amount / 100;
                break;
            }
        }

        return [
            'balance' => $availableBalance,
            'currency' => config('payment.stripe.currency', 'usd'),
        ];
    }

    public function processWebhook(): void
    {
        $payload = file_get_contents('php://input');
        $signature = request()->header('Stripe-Signature');

        if (!$this->client->verifyWebhookSignature($payload, $signature)) {
            throw new Exception('Invalid Stripe webhook signature', 401);
        }

        $event = $this->client->constructWebhookEvent($payload, $signature);

        // Delegate to webhook handler
        app(\Modules\Payment\Services\WebhookHandlers\StripeWebhookHandler::class)
            ->handle($event->type, $event->data->object);
    }

    protected function mapStripeStatus(string $status): string
    {
        return match($status) {
            'succeeded', 'paid' => 'success',
            'pending', 'in_transit' => 'pending',
            'failed', 'canceled' => 'failed',
            default => 'pending'
        };
    }
}
