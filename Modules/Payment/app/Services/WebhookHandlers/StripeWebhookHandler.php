<?php

namespace Modules\Payment\Services\WebhookHandlers;

use Exception;
use Modules\Payment\Enums\TransactionStatusEnum;
use Modules\Payment\Enums\TransactionTypeEnum;
use Modules\Payment\Repositories\Contracts\ProviderAccountRepositoryInterface;
use Modules\Payment\Repositories\Contracts\TransactionRepositoryInterface;
use Modules\Auth\Repositories\Contracts\AuthRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Core\Models\Wallet;

class StripeWebhookHandler
{
    public function __construct(
        protected ProviderAccountRepositoryInterface $providerAcctRepo,
        protected TransactionRepositoryInterface $transactionRepo,
        protected AuthRepositoryInterface $userRepo
    ) {}

    public function handle(string $event, $data): void
    {
        Log::info("Stripe webhook received", ['event' => $event, 'id' => $data->id ?? null]);

        match($event) {
            'payment_intent.succeeded' => $this->handlePaymentIntentSucceeded($data),
            'payout.paid' => $this->handlePayoutPaid($data),
            'payout.failed' => $this->handlePayoutFailed($data),
            'customer.created' => $this->handleCustomerCreated($data),
            default => Log::info("Unhandled Stripe event: {$event}")
        };
    }

    protected function handlePaymentIntentSucceeded($paymentIntent): void
    {
        DB::beginTransaction();

        try {
            $amount = $paymentIntent->amount / 100; // Convert from cents
            $customerId = $paymentIntent->customer;
            $reference = $paymentIntent->id;

            $providerAccount = $this->providerAcctRepo->findBy('provider_customer_id', $customerId);

            if (!$providerAccount) {
                throw new Exception("Provider account not found for customer: {$customerId}");
            }

            $user = $this->userRepo->find($providerAccount->user_id);
            $wallet = $user->wallet;

            $existingTransaction = $this->transactionRepo->findBy('reference', $reference);
            if ($existingTransaction) {
                Log::warning("Duplicate transaction", ['reference' => $reference]);
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
                'meta' => [
                    'provider' => 'stripe',
                    'payment_intent_id' => $paymentIntent->id,
                    'payment_method' => $paymentIntent->payment_method,
                    'stripe_fee' => ($paymentIntent->application_fee_amount ?? 0) / 100,
                ],
            ]);

            $transaction->transactables()->create([
                'transactable_type' => Wallet::class,
                'transactable_id' => $wallet->id,
                'amount' => $amount
            ]);

            $wallet->increment('balance', $amount);

            Log::info("Stripe deposit processed", [
                'user_id' => $user->id,
                'amount' => $amount,
                'reference' => $reference
            ]);

            DB::commit();

        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Stripe payment_intent.succeeded processing failed", [
                'error' => $e->getMessage(),
                'payment_intent' => $paymentIntent->id ?? null
            ]);
            throw $e;
        }
    }

    protected function handlePayoutPaid($payout): void
    {
        DB::beginTransaction();

        try {
            $reference = $payout->metadata->reference ?? $payout->id;

            $transaction = $this->transactionRepo->findBy('reference', $reference);

            if (!$transaction) {
                throw new Exception("Transaction not found: {$reference}");
            }

            $this->transactionRepo->update($transaction->id, [
                'status' => TransactionStatusEnum::Success,
                'meta' => array_merge($transaction->meta ?? [], [
                    'payout_id' => $payout->id,
                    'completed_at' => now(),
                ])
            ]);

            Log::info("Stripe payout completed", [
                'transaction_id' => $transaction->id,
                'payout_id' => $payout->id
            ]);

            DB::commit();

        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Stripe payout.paid processing failed", [
                'error' => $e->getMessage(),
                'payout' => $payout->id ?? null
            ]);
            throw $e;
        }
    }

    protected function handlePayoutFailed($payout): void
    {
        DB::beginTransaction();

        try {
            $reference = $payout->metadata->reference ?? $payout->id;

            $transaction = $this->transactionRepo->findBy('reference', $reference);

            if (!$transaction) {
                throw new Exception("Transaction not found: {$reference}");
            }

            $wallet = $transaction->wallet;

            // Refund wallet
            $wallet->increment('balance', $transaction->amount);

            $this->transactionRepo->update($transaction->id, [
                'status' => TransactionStatusEnum::Failed,
                'meta' => array_merge($transaction->meta ?? [], [
                    'failure_code' => $payout->failure_code,
                    'failure_message' => $payout->failure_message,
                    'failed_at' => now(),
                ])
            ]);

            Log::warning("Stripe payout failed, wallet refunded", [
                'transaction_id' => $transaction->id,
                'payout_id' => $payout->id,
                'amount' => $transaction->amount
            ]);

            DB::commit();

        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Stripe payout.failed processing error", [
                'error' => $e->getMessage(),
                'payout' => $payout->id ?? null
            ]);
            throw $e;
        }
    }

    protected function handleCustomerCreated($customer): void
    {
        Log::info("Stripe customer created", ['customer_id' => $customer->id]);
    }
}
