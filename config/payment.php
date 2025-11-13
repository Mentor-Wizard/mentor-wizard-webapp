<?php

declare(strict_types=1);

return [
    'default' => env('PAYMENT_GATEWAY', 'wayforpay'),

    'wayforpay' => [
        'merchant_account'    => env('WAYFORPAY_MERCHANT_ACCOUNT'),
        'merchant_secret_key' => env('WAYFORPAY_MERCHANT_SECRET_KEY'),
        'merchant_domain'     => env('WAYFORPAY_MERCHANT_DOMAIN', env('APP_URL')),
        'service_url'         => env('WAYFORPAY_SERVICE_URL'),
        'return_url'          => env('WAYFORPAY_RETURN_URL', '/payments/success'),
        'decline_url'         => env('WAYFORPAY_DECLINE_URL', '/payments/declined'),
    ],
];
