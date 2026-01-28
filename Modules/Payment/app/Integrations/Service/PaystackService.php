<?php
namespace Modules\Payment\Integrations\Service;

use App\Enums\KycStatus;
use Exception;
use Modules\Payment\Integrations\Clients\PaystackClient;
use Modules\Payment\Managers\Contracts\BankContract;
use Modules\Payment\Managers\Dtos\Bank\AccountDto;
use Modules\Payment\Managers\Dtos\Bank\CreateCustomerDto;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Payment\Enums\TransactionStatusEnum;
use Modules\Payment\Enums\TransactionTypeEnum;
use Modules\Payment\Managers\Dtos\Bank\AccountVerificationDto;
use Modules\Payment\Managers\Dtos\Bank\TransferDto;
use Modules\Payment\Managers\Dtos\Bank\TransferRecipientDto;
use Modules\Payment\Repositories\Contracts\TransactionRepositoryInterface;
use Modules\Payment\Repositories\Contracts\ProviderAccountRepositoryInterface;
use Modules\Auth\Repositories\Contracts\AuthRepositoryInterface;
use Modules\Core\Models\Wallet;
use Modules\Payment\Managers\Dtos\Bank\BankDto;

class PaystackService implements BankContract
{
    public function __construct(
        protected PaystackClient $client,
        protected TransactionRepositoryInterface $transactionRepo,
        protected ProviderAccountRepositoryInterface $providerAcctRepo,
        protected AuthRepositoryInterface $userRepo,
    ) {
    }
    public function createCustomer(array $data): CreateCustomerDto
    {
        $user = auth()->user();

        $payload = [
            'email' => $user->email,
            'first_name' => $data['first_name'] ?? $user->first_name,
            'last_name' => $data['last_name'] ?? $user->last_name,
            'phone_number' =>  $data['phone_number'] ?? $user->meta->phone_number
        ];

        $response = $this->client->createCustomer($payload);

        if (!$response['status']) {
            throw new Exception(
                $response['message'] ?? 'Unable to create customer',400);
        }
        return CreateCustomerDto::fromPaystack($payload, $response);

    }
    public function verifyKyc(CreateCustomerDto $dataObj): KycStatus
    {
        $data = $dataObj->toArray();

        $payload = [
            'customer_code' => $data['customerId'],
            'country' => 'NG',
            'type' => 'bank_account',
            'account_number' => $data['acctno'] ,
            'bank_code' => $data['bankCode'],
            'bvn' => $data['bvn'],
            'first_name' => $data['firstName'],
            'last_name' => $data['lastName'],
        ];

        $this->client->validateCustomer($payload,$data['customerId']);
        return KycStatus::PENDING;
    }
    public function createDepositAccount(CreateCustomerDto $dataObj):AccountDto
    {
        $data =  $dataObj->toArray();
        $payload = [
            'customer' => $data['customerId'],
            "preferred_bank"=>"wema-bank"
        ];
        $response = $this->client->createDepositAccount($payload);

        if (!$response['status']) {
            throw new Exception(
                $response['message'] ?? 'Unable to create customer',400);
        }
        return new AccountDto(
            providerAcctNumber:  $response['data']['account_number'],
            providerAcctname: $response['data']['account_name'],
            provider: 'paystack',
            bankId:  $response['data']['bank']['id'],
            bankName:  $response['data']['bank']['name'],
            bankCode:  $response['data']['bank']['slug'],
            currency:  $response['data']['currency'],
        );
    }

    public function listBanks(string $country = 'nigeria'): array
    {
        $response = $this->client->listBanks([
            'country' => $country,
            'perPage' => 100
        ]);

        if (!$response['status']) {
            throw new Exception('Unable to fetch banks', 400);
        }
        return array_map(function($bank) {
            return new BankDto(
                id: $bank['id'],
                name: $bank['name'],
                code: $bank['code'],
                slug: $bank['slug'],
                provider: 'paystack'
            );
        }, $response['data']);
    }

    public function resolveAccountNumber(string $accountNumber, string $bankCode): AccountVerificationDto
    {
        $response = $this->client->resolveAccount([
            'account_number' => $accountNumber,
            'bank_code' => $bankCode
        ]);

        if (!$response['status']) {
            throw new Exception(
                $response['message'] ?? 'Unable to resolve account', 400
            );
        }

        return new AccountVerificationDto(
            accountNumber: $response['data']['account_number'],
            accountName: $response['data']['account_name'],
            bankCode: $bankCode,
            bankId: $response['data']['bank_id'] ?? null
        );
    }

    public function createTransferRecipient(array $data): TransferRecipientDto
    {
        $payload = [
            'type' => 'nuban',
            'name' => $data['account_name'],
            'account_number' => $data['account_number'],
            'bank_code' => $data['bank_code'],
            'currency' => 'NGN'
        ];

        $response = $this->client->createRecipient($payload);


        if (!$response['status']) {
            throw new Exception(
                $response['message'] ?? 'Unable to create recipient', 400
            );
        }

        return new TransferRecipientDto(
            recipientCode: $response['data']['recipient_code'],
            recipientName: $response['data']['name'],
            accountNumber: $response['data']['details']['account_number'],
            bankCode: $data['bank_code'],
            bankName: $response['data']['details']['bank_name'],
            provider: 'paystack'
        );
    }

    public function initiateTransfer(array $data): TransferDto
    {
        $payload = [
            'source' => 'balance',
            'amount' => $data['amount'] * 100,
            'recipient' => $data['recipient_code'],
            'reason' => $data['reason'] ?? 'Wallet withdrawal',
            'reference' => $data['reference'] ?? $this->transactionRepo->generateTransactionReference()
        ];

        $response = $this->client->initiateTransfer($payload);


        if (!$response['status']) {
            throw new Exception(
                $response['message'] ?? 'Transfer failed', 400
            );
        }

        return new TransferDto(
            transferCode: $response['data']['transfer_code'],
            amount: $response['data']['amount'] / 100,
            status: $response['data']['status'],
            reference: $response['data']['reference'],
            recipientCode: $response['data']['recipient'],
            provider: 'paystack',
            reason: $data['reason'] ?? 'Wallet withdrawal'
        );
    }

    public function processWebhook():void
    {
        Log::info('webhook received with event type: '. request()->event);

        $request = request();
        $event = $request->event;
        $data = $request->data;
        match($event) {
            'charge.success' => $this->handleChargeSuccess($data),
            'transfer.success' => $this->handleTransferSuccess($data),
            'transfer.failed' => $this->handleTransferFailed($data),
            'transfer.reversed' => $this->handleTransferReversed($data),
            'customeridentification.success' => $this->handleKycSuccess($data),
            'customeridentification.failed' => $this->handleKycFailed($data),
            default => throw new Exception("Unhandled Paystack event: {$event}")
        };
    }

    protected function handleChargeSuccess(array $data): void
    {
        DB::beginTransaction();

        try {
            $reference = $data['reference'];
            $amount = $data['amount'] / 100; // Convert from kobo
            $customerCode = $data['customer']['customer_code'];
            $channel = $data['channel'] ?? 'unknown';

            // Find provider account by customer code
            $providerAccount = $this->providerAcctRepo->findBy('provider_customer_id', $customerCode);

            if (!$providerAccount) {
                throw new Exception("Provider account not found for customer code: {$customerCode}");
            }

            $user = $this->userRepo->find($providerAccount->user_id);
            $wallet = $user->wallet;

            // Check for duplicate transaction
            $existingTransaction = $this->transactionRepo->findBy('reference', $reference);
            if ($existingTransaction) {
                DB::rollBack();
                return;
            }

            // Create transaction
            $transaction = $this->transactionRepo->create([
                'wallet_id' => $wallet->id,
                'user_id' => $user->id,
                'type' => TransactionTypeEnum::Deposit,
                'amount' => $amount,
                'reference' => $reference,
                'status' => TransactionStatusEnum::Success,
                'metadata' => [
                    'channel' => $channel,
                    'provider' => 'paystack',
                    'customer_code' => $customerCode,
                    'paid_at' => $data['paid_at'] ?? now(),
                ]
            ]);

             // Create transactable entry linking transaction to wallet
            $transaction->transactables()->create([
                'transactable_type' => Wallet::class,
                'transactable_id' => $wallet->id,
                'amount' => $amount
            ]);
            // Credit wallet
            $wallet->increment('balance', $amount);
            DB::commit();
            return;

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    protected function handleTransferSuccess(array $data): void
    {
        DB::beginTransaction();

        try {
            $reference = $data['reference'];

            $transaction = $this->transactionRepo->findBy('reference', $reference);

            if (!$transaction) {
                throw new Exception("Transaction not found for reference: {$reference}");
            }

            // Update transaction status to completed
            $this->transactionRepo->update($transaction->id, [
                'status' => TransactionStatusEnum::Success,
                'meta' => array_merge($transaction->meta ?? [], [
                    'transfer_code' => $data['transfer_code'] ?? null,
                    'completed_at' => now(),
                ])
            ]);
            DB::commit();

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    protected function handleTransferFailed(array $data): void
    {
        DB::beginTransaction();

        try {
            $reference = $data['reference'];

            $transaction = $this->transactionRepo->findBy('reference', $reference);

            if (!$transaction) {
                throw new Exception("Transaction not found for reference: {$reference}");
            }
            $user = $this->userRepo->find($this->transactionRepo->user_id);
            $wallet = $user->wallet;

            // Refund wallet (wallet was debited optimistically)
            $wallet->increment('balance', $transaction->amount);

            // Update transaction status
            $this->transactionRepo->update($transaction->id, [
                'status' => TransactionStatusEnum::Failed,
                'metadata' => array_merge($transaction->metadata ?? [], [
                    'failure_reason' => $data['message'] ?? 'Transfer failed',
                    'failed_at' => now(),
                ])
            ]);

            DB::commit();

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    protected function handleTransferReversed(array $data): void
    {
        DB::beginTransaction();

        try {
            $reference = $data['reference'];

            $transaction = $this->transactionRepo->findBy('reference', $reference);

            if (!$transaction) {
                throw new Exception("Transaction not found for reference: {$reference}");
            }

            $user = $this->userRepo->find($this->transactionRepo->user_id);
            $wallet = $user->wallet;

            // Refund wallet
            $wallet->increment('balance', $transaction->amount);

            // Update transaction status
            $this->transactionRepo->update($transaction->id, [
                'status' => TransactionStatusEnum::Reversed,
                'meta' => array_merge($transaction->meta ?? [], [
                    'reversed_at' => now(),
                ])
            ]);
            DB::commit();

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }


    protected function handleKycSuccess(array $data): void
    {
        DB::beginTransaction();

        try {
            $customerCode = $data['customer_code'] ?? $data['customer']['customer_code'];

            // Find provider account by customer code
            $providerAccount = $this->providerAcctRepo->findBy('provider_customer_id', $customerCode);

            if (!$providerAccount) {
                throw new Exception("Provider account not found for customer code: {$customerCode}");
            }

            $user = $this->userRepo->find($providerAccount->user_id);

            // Update user KYC status
            $this->userRepo->update($user->id, [
                'kyc_status' => KycStatus::VERIFIED,
                'kyc_verified_at' => now(),
            ]);

            // Update provider account meta with verification details
            $meta = $providerAccount->meta ?? [];
            $meta['kyc_verified_at'] = now()->toDateTimeString();
            $meta['identification_type'] = $data['identification']['type'] ?? 'bank_account';

            $this->providerAcctRepo->update($providerAccount->id, [
                'meta' => $meta
            ]);
            DB::commit();

            // Optional: Send notification to user
            // event(new KycVerifiedEvent($user));

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    protected function handleKycFailed(array $data): void
    {
        DB::beginTransaction();

        try {
            $customerCode = $data['customer_code'] ?? $data['customer']['customer_code'];
            $reason = $data['reason'] ?? 'KYC verification failed';

            // Find provider account by customer code
            $providerAccount = $this->providerAcctRepo->findBy('provider_customer_id', $customerCode);

            if (!$providerAccount) {
                throw new Exception("Provider account not found for customer code: {$customerCode}");
            }

            $user = $this->userRepo->find($providerAccount->user_id);

            // Update user KYC status
            $this->userRepo->update($user->id, [
                'kyc_status' => KycStatus::FAILED,
            ]);

            // Update provider account meta with failure reason
            $meta = $providerAccount->meta ?? [];
            $meta['kyc_failed_at'] = now()->toDateTimeString();
            $meta['kyc_failure_reason'] = $reason;

            $this->providerAcctRepo->update($providerAccount->id, [
                'meta' => $meta
            ]);

            DB::commit();

            // Optional: Send notification to user
            // event(new KycFailedEvent($user, $reason));

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    protected function isValidSignature(Request $request): bool
    {
        $signature = (string)$request->header('x-paystack-signature');

        $expected = hash_hmac(
            'sha512',
            $request->getContent(),
            config('payment.paystack.secret_key')
        );

        return hash_equals($expected, $signature);
    }
    public function verifyTransfer(string $reference): TransferDto
    {
        throw new \Exception('Not implemented');
    }
    public function createAccount(AccountDto $dataObj):AccountDto
    {
        throw new \Exception('Not implemented');
    }
    public function getBalance(): array
    {
        // Fetch your Anchor account balance
        throw new Exception('Balance fetching not yet implemented for Paystack');
    }
}
