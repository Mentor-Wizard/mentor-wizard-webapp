<?php

declare(strict_types=1);

use App\Jobs\HandlePaymentWebhookJob;
use App\Listeners\DispatchPaymentWebhookJob;
use AratKruglik\WayForPay\Events\WayForPayCallbackReceived;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;

describe('DispatchPaymentWebhookJob', function (): void {
    it('dispatches HandlePaymentWebhookJob with the event payload', function (): void {
        Bus::fake();

        $payload = ['orderReference' => 'ref-123', 'transactionStatus' => 'Approved'];

        (new DispatchPaymentWebhookJob)->handle(new WayForPayCallbackReceived($payload));

        Bus::assertDispatched(fn (HandlePaymentWebhookJob $job): bool => $job->data === $payload);
    });

    it('is registered as a listener for WayForPayCallbackReceived', function (): void {
        Bus::fake();

        Event::dispatch(new WayForPayCallbackReceived(['orderReference' => 'ref-wired']));

        Bus::assertDispatched(fn (HandlePaymentWebhookJob $job): bool => $job->data['orderReference'] === 'ref-wired');
    });
});
