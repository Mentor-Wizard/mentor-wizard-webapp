<?php

declare(strict_types=1);

use App\Enums\PaymentStatusEnum;
use App\Jobs\HandlePaymentWebhookJob;
use App\Models\MentorSession;
use App\Models\Payment;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
});

describe('HandlePaymentWebhookJob', function (): void {
    describe('missing orderReference', function (): void {
        it('logs a warning and returns early when orderReference is missing', function (): void {
            Log::shouldReceive('warning')
                ->once()
                ->withArgs(fn (string $msg): bool => str_contains($msg, 'missing orderReference'));

            $job = new HandlePaymentWebhookJob([]);
            $job->handle();
        });
    });

    describe('payment not found', function (): void {
        it('logs a warning when no payment matches the orderReference', function (): void {
            Log::shouldReceive('warning')
                ->once()
                ->withArgs(fn (string $msg): bool => str_contains($msg, 'payment not found'));

            $job = new HandlePaymentWebhookJob(['orderReference' => 'non-existent-ref']);
            $job->handle();
        });
    });

    describe('queue configuration', function (): void {
        it('is dispatched to the payments queue', function (): void {
            $job = new HandlePaymentWebhookJob(['orderReference' => 'test']);
            expect($job->queue)->toBe('payments');
        });

        it('retries 3 times with 10s backoff', function (): void {
            $job = new HandlePaymentWebhookJob([]);
            expect($job->tries)->toBe(3)
                ->and($job->backoff)->toBe(10);
        });
    });

    describe('status transitions', function (): void {
        it('transitions PENDING to APPROVED and marks payable as paid', function (): void {
            $session = MentorSession::factory()->create(['is_paid' => false]);
            $payment = Payment::factory()->create([
                'payable_type'       => MentorSession::class,
                'payable_id'         => $session->getKey(),
                'order_reference'    => 'ref-pending-to-approved',
                'transaction_status' => PaymentStatusEnum::PENDING,
            ]);

            $job = new HandlePaymentWebhookJob([
                'orderReference'    => 'ref-pending-to-approved',
                'transactionStatus' => 'Approved',
                'paymentSystem'     => 'card',
            ]);
            $job->handle();

            expect($payment->fresh()->transaction_status)->toBe(PaymentStatusEnum::APPROVED)
                ->and($session->fresh()->is_paid)->toBeTrue();
        });

        it('transitions PENDING to DECLINED and marks payable as unpaid', function (): void {
            $session = MentorSession::factory()->create(['is_paid' => true]);
            $payment = Payment::factory()->create([
                'payable_type'       => MentorSession::class,
                'payable_id'         => $session->getKey(),
                'order_reference'    => 'ref-pending-to-declined',
                'transaction_status' => PaymentStatusEnum::PENDING,
            ]);

            $job = new HandlePaymentWebhookJob([
                'orderReference'    => 'ref-pending-to-declined',
                'transactionStatus' => 'Declined',
                'reason'            => 'Insufficient funds',
                'reasonCode'        => '1101',
            ]);
            $job->handle();

            expect($payment->fresh()->transaction_status)->toBe(PaymentStatusEnum::DECLINED)
                ->and($session->fresh()->is_paid)->toBeFalse();
        });

        it('transitions PENDING to REFUNDED', function (): void {
            $payment = Payment::factory()->create([
                'order_reference'    => 'ref-pending-to-refunded',
                'transaction_status' => PaymentStatusEnum::PENDING,
            ]);

            $job = new HandlePaymentWebhookJob([
                'orderReference'    => 'ref-pending-to-refunded',
                'transactionStatus' => 'Refunded',
            ]);
            $job->handle();

            expect($payment->fresh()->transaction_status)->toBe(PaymentStatusEnum::REFUNDED);
        });

        it('maps unknown WayForPay status to PENDING', function (): void {
            $payment = Payment::factory()->create([
                'order_reference'    => 'ref-unknown-status',
                'transaction_status' => PaymentStatusEnum::PENDING,
            ]);

            $job = new HandlePaymentWebhookJob([
                'orderReference'    => 'ref-unknown-status',
                'transactionStatus' => 'SomeUnknownStatus',
            ]);
            $job->handle();

            expect($payment->fresh()->transaction_status)->toBe(PaymentStatusEnum::PENDING);
        });
    });

    describe('idempotency', function (): void {
        it('does not change an already-APPROVED payment (terminal status)', function (): void {
            $payment = Payment::factory()->approved()->create([
                'order_reference' => 'ref-already-approved',
            ]);

            $job = new HandlePaymentWebhookJob([
                'orderReference'    => 'ref-already-approved',
                'transactionStatus' => 'Declined',
            ]);
            $job->handle();

            expect($payment->fresh()->transaction_status)->toBe(PaymentStatusEnum::APPROVED);
        });

        it('does not change an already-DECLINED payment (terminal status)', function (): void {
            $payment = Payment::factory()->declined()->create([
                'order_reference' => 'ref-already-declined',
            ]);

            $job = new HandlePaymentWebhookJob([
                'orderReference'    => 'ref-already-declined',
                'transactionStatus' => 'Approved',
            ]);
            $job->handle();

            expect($payment->fresh()->transaction_status)->toBe(PaymentStatusEnum::DECLINED);
        });

        it('does not change an already-REFUNDED payment (terminal status)', function (): void {
            $payment = Payment::factory()->refunded()->create([
                'order_reference' => 'ref-already-refunded',
            ]);

            $job = new HandlePaymentWebhookJob([
                'orderReference'    => 'ref-already-refunded',
                'transactionStatus' => 'Approved',
            ]);
            $job->handle();

            expect($payment->fresh()->transaction_status)->toBe(PaymentStatusEnum::REFUNDED);
        });
    });
});
