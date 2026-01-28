<?php
namespace Modules\Payment\Services;

use App\Models\User;
use Exception;
use App\Traits\ResponseTrait;
use Modules\Payment\Enums\TransactionStatusEnum;
use Modules\Payment\Enums\TransactionTypeEnum;
use Modules\Payment\Managers\BankManager;
use Modules\Payment\Repositories\Contracts\TransactionRepositoryInterface;
use Modules\Payment\Repositories\Contracts\WalletRepositoryInterface;
use Modules\Auth\Repositories\Contracts\AuthRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Modules\Circle\Models\Circle;
use Modules\Core\Models\Wallet;
use Modules\Payment\Repositories\Contracts\ProviderAccountRepositoryInterface;

class TransactionService
{
    use ResponseTrait;

    protected $bankProvider;

    public function __construct(
        protected BankManager $bankManager,
        protected TransactionRepositoryInterface $transactionRepo,
        protected AuthRepositoryInterface $userRepo,
        protected ProviderAccountRepositoryInterface $providerAcctRepo
    ) {
        $this->bankProvider = $this->bankManager->driver();
    }

    // ==================== WITHDRAWAL/TRANSFER ====================

    /**
     * Initiate withdrawal from wallet to bank account
     */
    public function initiateWithdrawal(int $userId,array $data)
    {
        DB::beginTransaction();

        try {
            $user = $this->userRepo->find($userId);
            $wallet = $user->wallet;

            // Step 1: Validate wallet balance
            if ($wallet->balance < $data['amount']) {
                throw new Exception('Insufficient wallet balance');
            }
            // Step 3: Create pending transaction
            $reference = $this->transactionRepo->generateTransactionReference();
            $transaction = $this->createTransaction(
                type: TransactionTypeEnum::Withdrawal,
                status: TransactionStatusEnum::Pending,
                amount: $data['amount'],
                userId: $userId,
                walletId: $wallet->id,
                reference: $reference
            );
             // Create transactable entry linking transaction to wallet
            $transaction->transactables()->create([
                'transactable_type' => Wallet::class,
                'transactable_id' => $wallet->id,
                'amount' => $data['amount']
            ]);

            // Step 4: Debit wallet immediately (optimistic)
            $wallet->decrement('amount', $data['amount']);

            // Step 5: Initiate transfer with bank provider
            $transferDto = $this->bankProvider->initiateTransfer([
                'amount' => $data['amount'],
                'recipient_code' => $data['recipientCode'],
                'reason' => $data['reason'] ?? 'Wallet withdrawal',
                'reference' => $reference
            ]);

            // Step 6: Update transaction with transfer details
            $this->transactionRepo->update($transaction->id, [
                'status' => $this->mapTransferStatus($transferDto->status),
                'meta' => [
                    'recipient_code' => $transferDto->recipientCode,
                    'provider_status' => $transferDto->status
                ]
            ]);

            DB::commit();

            return $this->success_response([
                'transaction' => $transaction->fresh(),
                'transfer' => $transferDto,
                'wallet' => $wallet->fresh()
            ], 'Withdrawal Processed Successfully',201);

        } catch (Exception $e) {
            DB::rollBack();
            return $this->error_response("unable to process withdrawa: " . $e->getMessage(), $e->getCode() ?: 400);
        }

    }
    // ==================== DEPOSIT HANDLING ====================

    /**
     * Process deposit from webhook
     * Called when user sends money to their DVA/deposit account
     */
    public function processWebhook()
    {
        DB::beginTransaction();

        try {
            $provider = config('app.bank_driver');

            if ($provider === 'paystack') {
                $this->bankProvider->processWebhook();
            } elseif ($provider === 'anchor') {
                throw new Exception('Not implemented');
            }

            DB::commit();

            return $this->success_response([],"Webhook Processed",200);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->error_response("failed" . $e->getMessage(), $e->getCode() ?: 400);
        }
    }


    // ==================== INTERNAL TRANSFERS (CIRCLE OPERATIONS) ====================

    /**
     * Transfer from user wallet to circle wallet
     */
    public function contributeToCircle(User $user,Circle $circle, float $amount)
    {
        DB::beginTransaction();

        try {

            $reference = $this->transactionRepo->generateTransactionReference();

            // Create debit transaction for user
            $debitTransaction = $this->createTransaction(
                type: TransactionTypeEnum::Contribution,
                status: TransactionStatusEnum::Success,
                amount: $amount,
                userId: $user->id,
                walletId: $user->wallet->id,
                circleId: $circle->id,
                reference: $reference . '_debit'
            );

            // Create credit transaction for circle
            $creditTransaction = $this->createTransaction(
                type: TransactionTypeEnum::CircleCredit,
                status: TransactionStatusEnum::Success,
                amount: $amount,
                userId: $user->id, // Who made the contribution
                walletId: $circle->wallet->id,
                circleId: $circle->id,
                reference: $reference . '_credit'
            );

            // Debit user wallet
            $user->wallet->decrement('balance',$amount);
            //increase circle wallet
            $circle->wallet->increment('balance',$amount);

            DB::commit();

            return $creditTransaction;

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }


    // ==================== TRANSACTION QUERIES ====================

    /**
     * Get user transaction history
     */
    public function getUserTransactions(int $userId, array $filters = [])
    {
        return $this->transactionRepo->getUserTransactions($userId, $filters);
    }


    /**
     * Get single transaction details
     */
    public function getTransaction(int $transactionId): ?object
    {
        return $this->transactionRepo->find($transactionId);
    }

     // ==================== BANK OPERATIONS ====================

    /**
     * Get list of banks
     */
    public function getBanks(string $country = 'NG')
    {
        try {
            return $this->success_response($this->bankProvider->listBanks($country),
                    "List of Banks Fetched",200);
        } catch (Exception $e) {
           return $this->error_response($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    /**
     * Verify/Resolve bank account number
     */
    public function verifyBankAccount(string $accountNumber, string $bankCode)
    {
        try {
            $verification = $this->bankProvider->resolveAccountNumber($accountNumber, $bankCode);

            return $this->success_response([
                'account_number' => $verification->accountNumber,
                'account_name' => $verification->accountName,
                'bank_code' => $verification->bankCode,
            ],"Bank Verified Successfully",200);
        } catch (Exception $e) {
             return $this->error_response("unable to Verify Bank: " . $e->getMessage(), $e->getCode() ?: 400);
        }
    }
    // ==================== TRANSACTION CREATION ====================

    public function createTransaction(
        TransactionTypeEnum $type,
        TransactionStatusEnum $status = TransactionStatusEnum::Pending,
        float $amount,
        int $userId,
        int $walletId,
        ?int $circleId = null,
        ?int $vaultId = null,
        string $reference = null
    ) {
        return $this->transactionRepo->create([
            'wallet_id' => $walletId,
            'user_id' => $userId,
            'circle_id' => $circleId,
            'vault_id' => $vaultId,
            'type' => $type,
            'amount' => $amount,
            'reference' => $reference ?? $this->transactionRepo->generateTransactionReference(),
            'status' => $status,
        ]);
    }

    // ==================== HELPER METHODS ====================

    protected function mapTransferStatus(string $providerStatus): TransactionStatusEnum
    {
        return match($providerStatus) {
            'success', 'successful' => TransactionStatusEnum::Success,
            'failed' => TransactionStatusEnum::Failed,
            'pending', 'otp' => TransactionStatusEnum::Pending,
            'reversed' => TransactionStatusEnum::Reversed,
            default => TransactionStatusEnum::Pending
        };
    }
}
