<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PaymentStatusEnum;
use App\Models\Currency;
use App\Models\MentorSession;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $session = MentorSession::factory()->create();

        return [
            'payable_type'       => MentorSession::class,
            'payable_id'         => $session->getKey(),
            'order_reference'    => fake()->uuid(),
            'amount'             => fake()->numberBetween(10000, 500000),
            'currency'           => Currency::factory(),
            'transaction_status' => PaymentStatusEnum::PENDING,
            'reason'             => null,
            'reason_code'        => null,
            'payment_system'     => null,
            'card_type'          => null,
            'issue_bank_name'    => null,
            'fee_amount'         => null,
            'fee_percentage'     => null,
            'net_amount'         => null,
            'refunded_at'        => null,
            'refund_amount'      => null,
        ];
    }

    public function approved(): static
    {
        return $this->state([
            'transaction_status' => PaymentStatusEnum::APPROVED,
            'payment_system'     => fake()->randomElement(['card', 'privat24', 'masterpass']),
            'card_type'          => fake()->randomElement(['Visa', 'MasterCard']),
            'fee_amount'         => fake()->numberBetween(100, 5000),
            'fee_percentage'     => fake()->randomFloat(4, 0.01, 0.035),
            'net_amount'         => fake()->numberBetween(5000, 495000),
        ]);
    }

    public function declined(): static
    {
        return $this->state([
            'transaction_status' => PaymentStatusEnum::DECLINED,
            'reason'             => 'Declined',
            'reason_code'        => '1100',
        ]);
    }

    public function refunded(): static
    {
        return $this->state([
            'transaction_status' => PaymentStatusEnum::REFUNDED,
            'refunded_at'        => now(),
            'refund_amount'      => fake()->numberBetween(10000, 500000),
        ]);
    }
}
