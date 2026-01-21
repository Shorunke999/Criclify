<?php
namespace Modules\Payment\Services;

use Exception;
use Illuminate\Support\Str;
use App\Traits\ResponseTrait;
use Illuminate\Support\Facades\Log;
use Modules\Circle\Enums\StatusEnum;
use Modules\Circle\Repositories\Contracts\ContributionRepositoryInterface;
use Modules\Core\Events\AuditLogged;
use Modules\Payment\Enums\TransactionStatusEnum;
use Modules\Payment\Enums\TransactionTypeEnum;
use Modules\Payment\Managers\PaymentManager;
use Modules\Payment\Models\Transaction;
use Modules\Payment\Repositories\Contracts\TransactionRepositoryInterface;

class PaymentService
{
    use ResponseTrait;
    protected $provider;
    public function __construct(
        protected TransactionRepositoryInterface $transactionRepo,
        protected ContributionRepositoryInterface $contributionRepo,
        protected PaymentManager $manager
    ) {
        $this->provider = $this->manager->driver(config('app.payment_driver','paystack'));
    }

    /**
     * Initialize payment
     */
    public function initiatePayment(array $data): array
    {
        try {
            $reference = $this->transactionRepo->generateTransactionReference();
            $transaction = $this->transactionRepo->create(array_merge($data, [
                'reference' => $reference,
                'status' => TransactionStatusEnum::Pending,
            ]));
            $providerResponse = $this->provider->initialize([
                'amount' => $transaction->amount,
                'reference' => $reference,
                'callback_url' => config('payment.callback_url'),
                'user_id' => $transaction->user_id,
                'metadata' => $transaction->meta,
            ]);

            return [
                'transaction_id' => $transaction->id,
                'reference' => $reference,
                'authorization_url' => $providerResponse['authorization_url'] ?? null,
            ];

        } catch (Exception $e) {
            $this->reportError($e, 'PaymentService', [
                'action' => 'initiate_payment',
            ]);

            throw $e;
        }
    }

    public function handleWebhook()
    {
        try {
             $reference = $this->provider->webhook();
             $this->processPayment($reference);
             return $this->success_response(
                [],
                'Webhook handled successfully',
                200);
        } catch (\Exception $th) {
            Log::error('Payment webhook error',[
                'error' => $th->getMessage(),
                'code' => $th->getCode() ?: 400
            ]);
            $this->reportError($th, 'PaymentService', [
                'action' => 'handle_webhook'
            ]);

            return $this->error_response(
                'Failed to handle webhook: ' . $th->getMessage(),
                400
            );
        }
    }

    public function createTransaction(TransactionTypeEnum $type,
         TransactionStatusEnum $status = TransactionStatusEnum::Pending,
         float $amount,
         int $userId,
         int $walletId,
         ?int $circleId = null,
         ?int $vaultId = null)
    {
          return $this->transactionRepo->create([
                'wallet_id' => $walletId,
                'user_id' => $userId,
                'circle_id' => $circleId,
                'vault_id' => $vaultId,
                'type' => $type,
                'amount' => $amount,
                'reference' => $this->transactionRepo->generateTransactionReference(),
                'status' => $status,
            ]);
    }
    private function processPayment($reference)
    {
        try {
            $transaction = $this->transactionRepo->findBy('reference', $reference);
            //processpayment would be here
        } catch (Exception $e) {
            $this->reportError($e, 'PaymentService', [
                'action' => 'process_payment',
                'reference' => $reference,
            ]);
            throw $e;
        }
    }

}
