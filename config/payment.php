<?php

return [
    'provider' => env('PAYMENT_GATEWAY_PROVIDER', 'midtrans'),
    'mode' => env('PAYMENT_GATEWAY_MODE', 'sandbox'),
    'qris_expiry_minutes' => (int) env('QRIS_EXPIRY_MINUTES', 15),

    'midtrans' => [
        'server_key' => env('MIDTRANS_SERVER_KEY'),
        'client_key' => env('MIDTRANS_CLIENT_KEY'),
        'sandbox_url' => 'https://api.sandbox.midtrans.com/v2',
        'production_url' => 'https://api.midtrans.com/v2',
    ],

    'xendit' => [
        'secret_key' => env('XENDIT_SECRET_KEY'),
        'callback_token' => env('XENDIT_CALLBACK_TOKEN'),
        'base_url' => 'https://api.xendit.co',
    ],
];
