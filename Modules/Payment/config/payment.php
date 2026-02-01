<?php

return
[
    'paystack' => [
        'base_url' => 'https://api.paystack.co',
        'secret_key' => env('SECRET_PAYSTACK_API_KEY','sk_test_6b3b2847914306dfbb5180c03849568d056f11b0'),
        'callback_url' => env('PAYSTACK_CALLBACK_URL', '127.0.0.1:8000/api/paystack/direct/debit/callback'),
    ],
     'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        'currency' => env('CASHIER_CURRENCY', 'usd'),

        'fees' => [
            'deposit' => [
                'type' => 'percentage',
                'rate' => 2.9, // 2.9%
                'flat_fee' => 0.30, // + $0.30
                'absorbed_by' => 'business',
            ],
            'payout' => [
                'type' => 'flat',
                'fee' => 0.25, // $0.25 per payout (Stripe Connect)
                'absorbed_by' => 'business',
            ],
        ],
    ],
];
