<?php

declare(strict_types=1);

use App\Enums\PaymentStatusEnum;
use App\Models\Payment;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

it('returns a PaymentStatusEnum instance from the model cast (not a string)', function (): void {
    $this->seed(RoleSeeder::class);

    $payment = Payment::factory()->approved()->create();
    $payment->refresh();

    expect($payment->transaction_status)->toBeInstanceOf(PaymentStatusEnum::class)
        ->toBe(PaymentStatusEnum::APPROVED)
        ->and($payment->transaction_status->value)->toBe('approved');
});
