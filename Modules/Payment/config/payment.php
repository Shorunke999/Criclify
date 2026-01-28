<?php

return
[
    'paystack' => [
        'base_url' => 'https://api.paystack.co',
        'secret_key' => env('SECRET_PAYSTACK_API_KEY','sk_test_6b3b2847914306dfbb5180c03849568d056f11b0'),
        'callback_url' => env('PAYSTACK_CALLBACK_URL', '127.0.0.1:8000/api/paystack/direct/debit/callback'),
    ],
];
