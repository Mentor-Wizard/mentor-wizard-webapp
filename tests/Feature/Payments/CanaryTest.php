<?php

declare(strict_types=1);

use App\Enums\PaymentStatusEnum;
use App\Models\Payment;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns a PaymentStatusEnum instance from the model cast (not a string)', function (): void {
    $this->seed(RoleSeeder::class);

    $payment = Payment::factory()->approved()->create();
    $payment->refresh();

    expect($payment->transaction_status)->toBeInstanceOf(PaymentStatusEnum::class);
    expect($payment->transaction_status)->toBe(PaymentStatusEnum::APPROVED);
    expect($payment->transaction_status->value)->toBe('approved');
});
