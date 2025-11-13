<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Payments\PaymentStatusEnum;
use App\Enums\Payments\PaymentTypeEnum;
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
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'mentor_session_id'  => MentorSession::factory(),
            'transaction_id'     => fake()->unique()->uuid(),
            'order_reference'    => fake()->unique()->regexify('[A-Z0-9]{20}'),
            'amount'             => fake()->numberBetween(10, 1000),
            'currency'           => Currency::factory(),
            'transaction_status' => fake()->randomElement(PaymentStatusEnum::cases()),
            'payment_type'       => fake()->randomElement(PaymentTypeEnum::cases()),
            'reason'             => fake()->optional()->sentence(),
            'reason_code'        => fake()->optional()->numerify('####'),
            'payment_system'     => fake()->randomElement(['WayForPay', 'MonoPay', 'LiqPay']),
            'card_type'          => fake()->randomElement(['Visa', 'Mastercard']),
            'card_pan'           => fake()->optional()->numerify('************####'),
            'issue_bank_name'    => fake()->optional()->company(),
            'phone'              => null,
            'account_number'     => null,
            'rectoken'           => null,
            'is_regular'         => false,
            'parent_payment_id'  => null,
            'metadata'           => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes): array => [
            'transaction_status' => PaymentStatusEnum::Approved,
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes): array => [
            'transaction_status' => PaymentStatusEnum::Pending,
        ]);
    }

    public function declined(): static
    {
        return $this->state(fn (array $attributes): array => [
            'transaction_status' => PaymentStatusEnum::Declined,
        ]);
    }

    public function refunded(): static
    {
        return $this->state(fn (array $attributes): array => [
            'transaction_status' => PaymentStatusEnum::Refunded,
            'refund_amount'      => $attributes['amount'],
            'refunded_at'        => now(),
        ]);
    }

    public function partiallyRefunded(): static
    {
        return $this->state(fn (array $attributes): array => [
            'transaction_status' => PaymentStatusEnum::PartiallyRefunded,
            'refund_amount'      => fake()->numberBetween(1, $attributes['amount'] - 1),
            'refunded_at'        => now(),
        ]);
    }

    public function purchase(): static
    {
        return $this->state(fn (array $attributes): array => [
            'payment_type' => PaymentTypeEnum::Purchase,
        ]);
    }

    public function refund(): static
    {
        return $this->state(fn (array $attributes): array => [
            'payment_type'      => PaymentTypeEnum::Refund,
            'parent_payment_id' => Payment::factory(),
        ]);
    }

    public function regular(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_regular' => true,
            'rectoken'   => fake()->uuid(),
        ]);
    }
}
