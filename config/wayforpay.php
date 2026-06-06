<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | WayForPay Merchant Credentials
    |--------------------------------------------------------------------------
    |
    | These credentials are used to authenticate with the WayForPay API.
    | You can find them in your WayForPay merchant cabinet.
    |
    */

    'merchant_account' => env('WAYFORPAY_MERCHANT_ACCOUNT', ''),
    'secret_key'       => env('WAYFORPAY_SECRET_KEY', ''),
    'merchant_domain'  => env('WAYFORPAY_MERCHANT_DOMAIN', ''),

    /*
    |--------------------------------------------------------------------------
    | Timeout
    |--------------------------------------------------------------------------
    |
    | The timeout for API requests in seconds.
    |
    */

    'timeout' => env('WAYFORPAY_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Debug Mode
    |--------------------------------------------------------------------------
    |
    | If enabled, the package will log requests and responses.
    |
    */

    'debug' => env('WAYFORPAY_DEBUG', false),
];
