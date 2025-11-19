<?php

declare(strict_types=1);

return [
    'default' => env('PAYMENT_GATEWAY', 'wayforpay'),

    'gateways' => [
        'wayforpay' => [
            'enabled'             => ! empty(env('WAYFORPAY_MERCHANT_ACCOUNT')),
            'name'                => 'WayForPay',
            'driver'              => 'wayforpay',
            'merchant_account'    => env('WAYFORPAY_MERCHANT_ACCOUNT'),
            'merchant_secret_key' => env('WAYFORPAY_MERCHANT_SECRET_KEY'),
            'merchant_domain'     => env('APP_URL'),
            'api_url'             => 'https://api.wayforpay.com/api',
            'service_url'         => '/api/payments/wayforpay/callback',
            'return_url'          => '/payments/success',
            'decline_url'         => '/payments/declined',
        ],

        'liqpay' => [
            'enabled'     => ! empty(env('LIQPAY_PUBLIC_KEY')),
            'name'        => 'LiqPay',
            'driver'      => 'liqpay',
            'public_key'  => env('LIQPAY_PUBLIC_KEY'),
            'private_key' => env('LIQPAY_PRIVATE_KEY'),
            'api_url'     => 'https://www.liqpay.ua/api/request',
            'service_url' => '/api/payments/liqpay/callback',
            'return_url'  => '/payments/success',
            'decline_url' => '/payments/declined',
        ],
    ],

    'features' => [
        'wayforpay' => [
            'purchase'         => true,
            'refund'           => true,
            'account2card'     => false,
            'account2account'  => false,
            'regular_payments' => false,
        ],
        'liqpay' => [
            'purchase'         => true,
            'refund'           => true,
            'account2card'     => false,
            'account2account'  => false,
            'regular_payments' => false,
        ],
    ],

    'settings' => [
        'default_currency' => 'UAH',
        'timeout'          => 30,
        'retry_attempts'   => 3,
        'callback_timeout' => 300,
    ],
];
