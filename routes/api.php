<?php

declare(strict_types=1);

use AratKruglik\WayForPay\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

Route::post('payments/webhook', WebhookController::class)->name('payments.webhook');
