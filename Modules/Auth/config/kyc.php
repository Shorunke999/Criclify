<?php

return [
    'provider' => env('KYC_DEFAULT_PROVIDER','smileid'),
    'smileid' => [
        'sandbox' => env('SMILEID_SANDBOX', true),
        'partner_id' => env('SMILEID_PARTNER_ID'),
        'api_key' => env('SMILEID_API_KEY'),
        'callback_url' => env('SMILEID_CALLBACK_URL', url('/api/kyc/callback')),

        // Supported countries and ID types
        'supported_countries' => [
            'NG' => ['PASSPORT', 'DRIVERS_LICENSE', 'NATIONAL_ID', 'VOTER_ID'],
            'KE' => ['PASSPORT', 'NATIONAL_ID', 'DRIVERS_LICENSE'],
            'GH' => ['PASSPORT', 'NATIONAL_ID', 'DRIVERS_LICENSE', 'VOTER_ID'],
        ],
    ]

];
