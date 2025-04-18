<?php

namespace Database\Seeders;

use App\Models\Currency;
use App\Enums\CurrencyEnum;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class CurrencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (CurrencyEnum::cases() as $currency) {
            Currency::factory()->create([
                'name' => $currency->name,
                'symbol' => $currency->value
            ]);
        }
    }
}
