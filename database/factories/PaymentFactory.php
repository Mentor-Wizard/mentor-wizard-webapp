<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Currency;
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
            'order_reference' => fake()->sentence(20),
            'amount' => fake()->numberBetween(1, 100),
            'currency' => Currency::factory(),
            'transaction_status' => fake()->sentence(20),
            'reason' => fake()->sentence(20),
            'reason_code' => fake()->sentence(10),
            'payment_system' => fake()->sentence(20),
            'card_type' => fake()->sentence(20),
            'issue_bank_name' => fake()->sentence(20),
        ];
    }
}
