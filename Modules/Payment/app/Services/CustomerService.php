<?php
namespace Modules\Payment\Services;

use App\Enums\KycStatus;
use Exception;
use App\Traits\ResponseTrait;
use Modules\Payment\Managers\BankManager;
use Modules\Payment\Managers\Dtos\Bank\CreateCustomerDto;
use Modules\Payment\Managers\Dtos\Bank\AccountDto;
use Modules\Payment\Managers\Dtos\Bank\TransferRecipientDto;
use Modules\Auth\Repositories\Contracts\AuthRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Modules\Payment\Repositories\Contracts\ProviderAccountRepositoryInterface;
use Modules\Payment\Repositories\Contracts\WithdrawalAccountRepositoryInterface;

class CustomerService
{
    use ResponseTrait;

    protected $bankProvider;

    public function __construct(
        protected BankManager $bankManager,
        protected ProviderAccountRepositoryInterface $providerRepo,
        protected WithdrawalAccountRepositoryInterface $withdrawalAccountRepo,
        protected AuthRepositoryInterface $userRepo
    ) {
        $this->bankProvider = $this->bankManager->driver();
    }

    // ==================== CUSTOMER ONBOARDING ====================

    /**
     * Onboard a new customer with bank provider
     * Creates customer, verifies KYC, and creates deposit account
     */
    public function onboardCustomer(array $data)
    {
        DB::beginTransaction();

        try {
            $user = auth()->user();
            $provider = config('app.bank_driver');

            // Check if user already has an account with this provider
            $existingProviderAccount = $this->providerRepo->findByUserAndProvider($user->id, $provider);

            if ($existingProviderAccount && $existingProviderAccount->account_number) {
                return $this->error_response('Customer already onboarded with this provider', 409);
            }

            // Step 1: Create customer (or skip if already created)
            if (!$existingProviderAccount || !$existingProviderAccount->provider_customer_id) {
                $customerDto = $this->bankProvider->createCustomer($data);
            } else {
                // Use existing customer data
                $customerDto = CreateCustomerDto::fromArray(
                    array_merge(
                        $existingProviderAccount->meta ?? [],
                        ['customerId' => $existingProviderAccount->provider_customer_id]
                    )
                );
                $kycStatus = $user->kyc_status ?? KycStatus::PENDING;
            }
            // Step 2: Verify KYC (if data provided)
            if($user->kyc_status === KycStatus::PENDING)
            {
                $kycStatus = $this->bankProvider->verifyKyc($customerDto);
                // Update user KYC status
                $this->userRepo->update($user->id, [
                    'kyc_status' => $kycStatus,
                    'kyc_verified_at' => $kycStatus === KycStatus::VERIFIED ? now() : null,
                ]);
            }
            // Step 3: Create deposit account
            $accountDto = $this->bankProvider->createDepositAccount($customerDto);

            // Step 4: For Anchor, create actual account number (2-step process)
            if ($provider === 'anchor' && !$accountDto->providerAcctNumber) {
                $accountDto = $this->bankProvider->createAccount($accountDto);
            }

            // Step 6: Create or update provider account with merged data
            $providerAccountData = array_merge(
                ['user_id' => $user->id, 'provider' => $provider],
                $accountDto->mergeWithCustomer($customerDto)
            );

            $this->providerRepo->updateOrCreate(
                ['user_id' => $user->id, 'provider' => $provider],
                $providerAccountData
            );

            DB::commit();

            return $this->success_response([
                'account_number' => $accountDto->providerAcctNumber,
                'bank_name' => $accountDto->bankName,
                'account_name' => $accountDto->accountName,
                'currency' => $accountDto->currency,
            ], 'Customer onboarded successfully', 201);

        } catch (Exception $e) {
            DB::rollBack();
            return $this->error_response($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Get customer details
     */
    public function getCustomerDetails(int $userId)
    {
        try{
            $user = $this->userRepo->find($userId);
            $provider = config('app.bank_driver');
            $providerAccount = $this->providerRepo->findByUserAndProvider($userId, $provider);
            $wallet = $user->wallet;

            if (!$providerAccount) {
                throw new Exception('Customer not onboarded with provider');
            }

            return $this->success_response([
                'account_id' => $providerAccount->provider_account_id,
                'account_number' => $providerAccount->account_number,
                'bank_name' => $providerAccount->meta['bank_name'] ?? null,
                'bank_code' => $providerAccount->meta['bank_code'] ?? null,
                'account_name' => $providerAccount->meta['account_name'] ?? null,
                'currency' => $providerAccount->currency,
                'kyc_status' => $user->kyc_status,
                'kyc_verified_at' => $user->kyc_verified_at,
                'wallet_balance' => $wallet->balance ?? 0,
                'customer_meta' => $providerAccount->meta,
            ], 'Customer Data fetched Successfully',200);
        } catch (Exception $e) {
            return $this->error_response($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    // ==================== WITHDRAWAL ACCOUNT SETUP ====================

    /**
     * Setup withdrawal account for user
     * Creates transfer recipient with bank provider
     */
    public function addWithdrawalAccount(int $userId, array $data)
    {
        DB::beginTransaction();

        try {
            // Step 1: Verify the account first
            $verification = $this->verifyBankAccount(
                $data['account_number'],
                $data['bank_code']
            );

            // Step 2: Create transfer recipient
            $recipientDto = $this->bankProvider->createTransferRecipient([
                'account_name' => $verification['account_name'],
                'account_number' => $verification['account_number'],
                'bank_code' => $verification['bank_code'],
            ]);

            // Step 3: Save to user's withdrawal accounts
            $withdrawalAccount = $this->saveWithdrawalAccount($userId, $recipientDto, $data);

            DB::commit();

            return $this->success_response($withdrawalAccount, "WithdrawalAccount Created Successfully", 201);

        } catch (Exception $e) {
            DB::rollBack();
            return $this->error_response("unable to create withdrawal account: " . $e->getMessage(), $e->getCode() ?: 400);
        }

    }

    /**
     * Get user's withdrawal accounts
     */
    public function getWithdrawalAccounts(int $userId)
    {

        try {
            $withdrawalAccount = $this->withdrawalAccountRepo->findBy('user_id', $userId);
            return $this->success_response($withdrawalAccount, "WithdrawalAccount Fetched Successfully", 200);

        } catch (Exception $e) {

            return $this->error_response("unable to fetch withdrawal account: " . $e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Remove withdrawal account
     */
    public function removeWithdrawalAccount(int $userId, int $accountId)
    {
        DB::beginTransaction();
         try {
            $withdrawalAccount = $this->withdrawalAccountRepo->findBy('recipient_code', $accountId);
            if (!$withdrawalAccount) {
                return $this->error_response("Account not found", 404);
            }

            // Fix: Compare user_id properly
            if ($userId != $withdrawalAccount->user_id) {
                return $this->error_response("Unauthorized action", 403);
            }

            $this->withdrawalAccountRepo->delete($withdrawalAccount->id);

            DB::commit();
            return $this->success_response($withdrawalAccount, "WithdrawalAccount Deleted Successfully", 200);

        } catch (Exception $e) {
            DB::rollBack();
            return $this->error_response("unable to Delete withdrawal account: " . $e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Set default withdrawal account
     */
    // In setDefaultWithdrawalAccount method - Fix comparison and query bugs
    public function setDefaultWithdrawalAccount(int $userId, string $recipientCode) // Changed to string
    {
        DB::beginTransaction();
        try {
            $withdrawalAccount = $this->withdrawalAccountRepo->findBy('recipient_code', $recipientCode);

            if (!$withdrawalAccount) {
                return $this->error_response("Account not found", 404);
            }

            // Fix: Compare user_id properly
            if ($userId != $withdrawalAccount->user_id) {
                return $this->error_response("Unauthorized action", 403);
            }

            // Fix: Get all user accounts properly
            $userAccounts = $this->withdrawalAccountRepo->findBy('user_id', $userId);

            foreach ($userAccounts as $acct) {
                if ($withdrawalAccount->id === $acct->id) {
                    $this->withdrawalAccountRepo->update($acct->id, [
                        'is_default' => true
                    ]);
                } else {
                    $this->withdrawalAccountRepo->update($acct->id, [
                        'is_default' => false
                    ]);
                }
            }

            DB::commit();

            return $this->success_response(
                $withdrawalAccount->fresh(),
                "Default withdrawal account set successfully",
                200
            );

        } catch (Exception $e) {
            DB::rollBack();
            return $this->error_response("Unable to update withdrawal account: " . $e->getMessage(), $e->getCode() ?: 400);
        }
    }

    // ==================== HELPER METHODS ====================


    protected function saveWithdrawalAccount(int $userId, TransferRecipientDto $dto, array $data)
    {
        return $this->withdrawalAccountRepo->create([
            'user_id' => $userId,
            'recipient_code' => $dto->recipientCode,
            'account_number' => $dto->accountNumber,
            'account_name' => $dto->recipientName,
            'bank_code' => $dto->bankCode,
            'bank_name' => $dto->bankName,
            'provider' => $dto->provider,
            'is_default' => $data['is_default'] ?? false,
        ]);
    }
}
