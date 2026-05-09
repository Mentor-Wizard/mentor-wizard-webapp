<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\CurrencyEnum;
use App\Models\Currency;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (CurrencyEnum::cases() as $currency) {
            Currency::query()->updateOrCreate(
                ['name' => $currency->name],
                [
                    'symbol'        => $currency->value,
                    'exchange_rate' => $currency->exchangeRate(),
                ],
            );
        }
    }
}
