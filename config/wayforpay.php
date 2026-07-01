<?php

declare(strict_types=1);

$sandbox = (bool) env('WAYFORPAY_SANDBOX', false);

return [
    'merchant_account' => $sandbox
        ? env('WAYFORPAY_SANDBOX_MERCHANT_ACCOUNT', 'test_merch_n1')
        : env('WAYFORPAY_MERCHANT_ACCOUNT', ''),

    'secret_key' => $sandbox
        ? env('WAYFORPAY_SANDBOX_SECRET_KEY', 'flk3409refn54t54t*FNJRET')
        : env('WAYFORPAY_SECRET_KEY', ''),

    'merchant_domain' => env('WAYFORPAY_MERCHANT_DOMAIN', ''),

    'timeout' => env('WAYFORPAY_TIMEOUT', 30),

    'debug' => env('WAYFORPAY_DEBUG', false),

    'sandbox' => $sandbox,
];
