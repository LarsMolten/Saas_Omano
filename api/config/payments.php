<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Payment Gateway
    |--------------------------------------------------------------------------
    |
    | Set to 'fake' for local development (no real operator calls).
    | Set to 'mvola', 'orange_money', or 'airtel_money' for production.
    |
    */

    'gateway' => env('PAYMENT_GATEWAY', 'fake'),

    /*
    |--------------------------------------------------------------------------
    | Fake Gateway
    |--------------------------------------------------------------------------
    |
    | Secret used to authenticate fake webhook payloads in local dev.
    |
    */

    'fake_webhook_secret' => env('FAKE_WEBHOOK_SECRET', 'fake-secret-dev'),

    /*
    |--------------------------------------------------------------------------
    | Webhook Secrets (per operator)
    |--------------------------------------------------------------------------
    |
    | Production webhook signature secrets for each mobile money operator.
    |
    */

    'mvola_webhook_secret' => env('MVOLA_WEBHOOK_SECRET'),
    'orange_webhook_secret' => env('ORANGE_WEBHOOK_SECRET'),
    'airtel_webhook_secret' => env('AIRTEL_WEBHOOK_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Gateway Map
    |--------------------------------------------------------------------------
    |
    | Maps operator key to its gateway class.
    |
    */

    'gateways' => [
        'mvola' => \App\Services\Gateways\MvolaGateway::class,
        'orange_money' => \App\Services\Gateways\OrangeMoneyGateway::class,
        'airtel_money' => \App\Services\Gateways\AirtelMoneyGateway::class,
        'fake' => \App\Services\Gateways\FakeGateway::class,
    ],

];
