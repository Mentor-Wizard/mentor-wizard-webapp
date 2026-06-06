<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Jobs\HandlePaymentWebhookJob;
use AratKruglik\WayForPay\Events\WayForPayCallbackReceived;

class DispatchPaymentWebhookJob
{
    public function handle(WayForPayCallbackReceived $event): void
    {
        dispatch(new HandlePaymentWebhookJob($event->data));
    }
}
