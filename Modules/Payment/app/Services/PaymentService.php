<?php
namespace Modules\Payment\Services;

use Exception;
use Illuminate\Support\Str;
use App\Traits\ResponseTrait;
use Illuminate\Support\Facades\Log;
use Modules\Circle\Enums\StatusEnum;
use Modules\Circle\Repositories\CircleWalletRepository;
use Modules\Circle\Repositories\Contracts\ContributionRepositoryInterface;
use Modules\Core\Events\AuditLogged;
use Modules\Payment\Enums\TransactionStatusEnum;
use Modules\Payment\Enums\TransactionTypeEnum;
use Modules\Payment\Managers\PaymentManager;
use Modules\Payment\Repositories\Contracts\TransactionRepositoryInterface;

class PaymentService
{
    use ResponseTrait;
    protected $provider;
    public function __construct(
        protected TransactionRepositoryInterface $transactionRepo,
        protected ContributionRepositoryInterface $contributionRepo,
        protected CircleWalletRepository $walletRepo,
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
            $reference = Str::uuid()->toString();

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

    private function processPayment($reference)
    {
        try {
            $transaction = $this->transactionRepo->findBy('reference', $reference);
            match($transaction->type){
                TransactionTypeEnum::Contribution => $this->handleContributionPayment($transaction),
                default => null,
            };
        } catch (Exception $e) {
            $this->reportError($e, 'PaymentService', [
                'action' => 'process_payment',
                'reference' => $reference,
            ]);
            throw $e;
        }
    }

    private function handleContributionPayment($transaction)
    {
        $contributionIds = $transaction->type_ids ?? [];
        $amountRemaining = $transaction->amount;

        foreach ($contributionIds as $contributionId) {

            if ($amountRemaining <= 0) break;

            $contribution = $this->contributionRepo
                ->findBy('id', $contributionId);

            if (! $contribution) continue;

            $due = $contribution->amount - $contribution->paid_amount;

            // FULL PAYMENT
            if ($amountRemaining >= $due) {
                $contribution->update([
                    'paid_amount' => $contribution->amount,
                    'status' => StatusEnum::Paid,
                    'paid_at' => now(),
                ]);

                $this->walletRepo->creditCircleWallet(
                    $contribution->circle_id,
                    $due
                );

                $amountRemaining -= $due;
            }
            // PARTIAL PAYMENT
            else {
                $contribution->update([
                    'paid_amount' => $contribution->paid_amount + $amountRemaining,
                    'status' => StatusEnum::Partpayment,
                ]);

                $this->walletRepo->creditCircleWallet(
                    $contribution->circle_id,
                    $amountRemaining
                );

                $amountRemaining = 0;
            }
        }
        $transaction->status = TransactionStatusEnum::Success;
        $transaction->save();
        event(new AuditLogged(
            action: \App\Enums\AuditAction::CONTRIBUTION_PAID->value,
            entityType: get_class($transaction),
            entityId: $transaction->id,
            userId: $transaction->user_id,
            metadata: [
                'amount' => $transaction->amount,
                'type' => $transaction->type,
                'type_ids' => $transaction->type_ids,
            ]
        ));
    }

}
