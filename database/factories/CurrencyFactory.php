<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CurrencyEnum;
use App\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Currency>
 */
class CurrencyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $currency = fake()->randomElement(CurrencyEnum::cases());

        return [
            'name'          => $currency->name,
            'slug'          => fake()->slug(),
            'symbol'        => $currency->value,
            'exchange_rate' => $currency->exchangeRate(),
        ];
    }
}
