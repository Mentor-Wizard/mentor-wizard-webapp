<?php

declare(strict_types=1);

use App\Actions\Payments\CheckPaymentStatusAction;
use App\Enums\PaymentStatusEnum;
use App\Models\Payment;
use AratKruglik\WayForPay\Facades\WayForPay;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
});

describe('CheckPaymentStatusAction', function (): void {
    it('returns the status from WayForPay and saves it when different from current', function (): void {
        WayForPay::shouldReceive('checkStatus')->once()->andReturn(['transactionStatus' => 'Approved']);

        $payment = Payment::factory()->create([
            'transaction_status' => PaymentStatusEnum::PENDING,
        ]);

        $result = CheckPaymentStatusAction::run($payment);

        expect($result)->toBe(PaymentStatusEnum::APPROVED)
            ->and($payment->fresh()->transaction_status)->toBe(PaymentStatusEnum::APPROVED);
    });

    it('does not save when status is the same as current', function (): void {
        WayForPay::shouldReceive('checkStatus')->once()->andReturn(['transactionStatus' => 'Approved']);

        $payment = Payment::factory()->approved()->create();
        $updatedAt = $payment->updated_at;

        $result = CheckPaymentStatusAction::run($payment);

        expect($result)->toBe(PaymentStatusEnum::APPROVED)
            ->and($payment->fresh()->updated_at->toIso8601String())->toBe($updatedAt->toIso8601String());
    });

    it('maps unknown WayForPay status to PENDING', function (): void {
        WayForPay::shouldReceive('checkStatus')->once()->andReturn(['transactionStatus' => 'UnknownStatus']);

        $payment = Payment::factory()->create([
            'transaction_status' => PaymentStatusEnum::PENDING,
        ]);

        $result = CheckPaymentStatusAction::run($payment);

        expect($result)->toBe(PaymentStatusEnum::PENDING);
    });

    it('returns DECLINED when WayForPay reports Declined', function (): void {
        WayForPay::shouldReceive('checkStatus')->once()->andReturn(['transactionStatus' => 'Declined']);

        $payment = Payment::factory()->create([
            'transaction_status' => PaymentStatusEnum::PENDING,
        ]);

        $result = CheckPaymentStatusAction::run($payment);

        expect($result)->toBe(PaymentStatusEnum::DECLINED)
            ->and($payment->fresh()->transaction_status)->toBe(PaymentStatusEnum::DECLINED);
    });
});
