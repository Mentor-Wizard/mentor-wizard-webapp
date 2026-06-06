<?php

declare(strict_types=1);

use App\Actions\Payments\RefundPaymentAction;
use App\Enums\PaymentStatusEnum;
use App\Models\MentorSession;
use App\Models\Payment;
use AratKruglik\WayForPay\Facades\WayForPay;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
});

describe('RefundPaymentAction', function (): void {
    it('returns false immediately for a PENDING payment (non-APPROVED)', function (): void {
        WayForPay::shouldReceive('refund')->never();

        $payment = Payment::factory()->create([
            'transaction_status' => PaymentStatusEnum::PENDING,
        ]);

        $result = RefundPaymentAction::run($payment);

        expect($result)->toBeFalse();
        expect($payment->fresh()->transaction_status)->toBe(PaymentStatusEnum::PENDING);
    });

    it('returns false immediately for a DECLINED payment', function (): void {
        WayForPay::shouldReceive('refund')->never();

        $payment = Payment::factory()->declined()->create();

        $result = RefundPaymentAction::run($payment);

        expect($result)->toBeFalse();
    });

    it('returns false immediately for a REFUNDED payment', function (): void {
        WayForPay::shouldReceive('refund')->never();

        $payment = Payment::factory()->refunded()->create();

        $result = RefundPaymentAction::run($payment);

        expect($result)->toBeFalse();
    });

    it('returns false when WayForPay refund call fails', function (): void {
        WayForPay::shouldReceive('refund')->once()->andReturn(['transactionStatus' => 'Declined', 'status' => 'error']);

        $payment = Payment::factory()->approved()->create();

        $result = RefundPaymentAction::run($payment);

        expect($result)->toBeFalse();
        expect($payment->fresh()->transaction_status)->toBe(PaymentStatusEnum::APPROVED);
    });

    it('transitions APPROVED to REFUNDED and marks payable as unpaid when WayForPay returns Refunded', function (): void {
        WayForPay::shouldReceive('refund')->once()->andReturn(['transactionStatus' => 'Refunded']);

        $session = MentorSession::factory()->create(['is_paid' => true]);
        $payment = Payment::factory()->approved()->create([
            'payable_type' => MentorSession::class,
            'payable_id'   => $session->getKey(),
        ]);

        $result = RefundPaymentAction::run($payment);

        expect($result)->toBeTrue();
        expect($payment->fresh()->transaction_status)->toBe(PaymentStatusEnum::REFUNDED);
        expect($payment->fresh()->refunded_at)->not->toBeNull();
        expect($session->fresh()->is_paid)->toBeFalse();
    });

    it('transitions APPROVED to REFUNDED when WayForPay returns status=success', function (): void {
        WayForPay::shouldReceive('refund')->once()->andReturn(['status' => 'success']);

        $payment = Payment::factory()->approved()->create();

        $result = RefundPaymentAction::run($payment);

        expect($result)->toBeTrue();
        expect($payment->fresh()->transaction_status)->toBe(PaymentStatusEnum::REFUNDED);
    });
});
