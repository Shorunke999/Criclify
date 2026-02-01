<?php

namespace Modules\Payment\Integrations\Clients;

use Stripe\StripeClient as StripeSDK;

class StripeClient
{
    protected $stripe;

    public function __construct()
    {
        $this->stripe = new StripeSDK(config('payment.stripe.secret'));
    }

    /**
     * Create customer
     */
    public function createCustomer(array $data)
    {
        return $this->stripe->customers->create($data);
    }

    /**
     * Create bank account (for payouts)
     */
    public function createBankAccount(string $customerId, array $data)
    {
        return $this->stripe->customers->createSource($customerId, $data);
    }

    /**
     * Create payment intent (for deposits)
     */
    public function createPaymentIntent(array $data)
    {
        return $this->stripe->paymentIntents->create($data);
    }

    /**
     * Create payout
     */
    public function createPayout(array $data)
    {
        return $this->stripe->payouts->create($data);
    }

    /**
     * Retrieve payment intent
     */
    public function retrievePaymentIntent(string $paymentIntentId)
    {
        return $this->stripe->paymentIntents->retrieve($paymentIntentId);
    }

    /**
     * List payment methods
     */
    public function listPaymentMethods(string $customerId)
    {
        return $this->stripe->paymentMethods->all([
            'customer' => $customerId,
            'type' => 'card',
        ]);
    }

    /**
     * Attach payment method to customer
     */
    public function attachPaymentMethod(string $paymentMethodId, string $customerId)
    {
        return $this->stripe->paymentMethods->attach($paymentMethodId, [
            'customer' => $customerId,
        ]);
    }

    /**
     * Create account (Stripe Connect - for creators/merchants)
     */
    public function createConnectAccount(array $data)
    {
        return $this->stripe->accounts->create($data);
    }

    /**
     * Verify webhook signature
     */
    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        try {
            \Stripe\Webhook::constructEvent(
                $payload,
                $signature,
                config('payment.stripe.webhook_secret')
            );
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Construct webhook event
     */
    public function constructWebhookEvent(string $payload, string $signature)
    {
        return \Stripe\Webhook::constructEvent(
            $payload,
            $signature,
            config('payment.stripe.webhook_secret')
        );
    }
}
