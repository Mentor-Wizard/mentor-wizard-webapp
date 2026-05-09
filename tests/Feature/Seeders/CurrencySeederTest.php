<?php

declare(strict_types=1);

use App\Enums\CurrencyEnum;
use App\Models\Currency;
use Database\Seeders\CurrencySeeder;

describe('CurrencySeeder idempotency', function (): void {
    it('creates exactly 4 currency rows on first run', function (): void {
        (new CurrencySeeder)->run();

        expect(Currency::query()->count())->toBe(4);
    });

    it('does not create duplicate rows when run a second time', function (): void {
        (new CurrencySeeder)->run();
        (new CurrencySeeder)->run();

        expect(Currency::query()->count())->toBe(4);
    });

    it('does not create duplicate rows when run three times', function (): void {
        (new CurrencySeeder)->run();
        (new CurrencySeeder)->run();
        (new CurrencySeeder)->run();

        expect(Currency::query()->count())->toBe(4);
    });

    it('seeds all four expected currencies', function (): void {
        (new CurrencySeeder)->run();

        $names = Currency::query()->pluck('name')->sort()->values()->toArray();

        expect($names)->toBe(['EUR', 'GBP', 'UAH', 'USD']);
    });

    it('seeds correct exchange rates for all currencies', function (): void {
        (new CurrencySeeder)->run();

        foreach (CurrencyEnum::cases() as $currency) {
            $row = Currency::query()->where('name', $currency->name)->first();

            expect($row)->not->toBeNull()
                ->and((float) $row->exchange_rate)->toBe($currency->exchangeRate());
        }
    });

    it('seeds correct symbols for all currencies', function (): void {
        (new CurrencySeeder)->run();

        foreach (CurrencyEnum::cases() as $currency) {
            $row = Currency::query()->where('name', $currency->name)->first();

            expect($row)->not->toBeNull()
                ->and($row->symbol)->toBe($currency->value);
        }
    });

    it('updates exchange_rate if re-run after manual change', function (): void {
        (new CurrencySeeder)->run();

        Currency::query()->where('name', 'USD')->update(['exchange_rate' => 99.0]);

        (new CurrencySeeder)->run();

        $usd = Currency::query()->where('name', 'USD')->first();

        expect((float) $usd->exchange_rate)->toBe(CurrencyEnum::USD->exchangeRate());
    });

    it('USD has exchange rate of 1.0', function (): void {
        (new CurrencySeeder)->run();

        $usd = Currency::query()->where('name', 'USD')->first();

        expect((float) $usd->exchange_rate)->toBe(1.0);
    });

    it('EUR has exchange rate of 1.08', function (): void {
        (new CurrencySeeder)->run();

        $eur = Currency::query()->where('name', 'EUR')->first();

        expect((float) $eur->exchange_rate)->toBe(1.08);
    });

    it('GBP has exchange rate of 1.27', function (): void {
        (new CurrencySeeder)->run();

        $gbp = Currency::query()->where('name', 'GBP')->first();

        expect((float) $gbp->exchange_rate)->toBe(1.27);
    });

    it('UAH has exchange rate of 0.024', function (): void {
        (new CurrencySeeder)->run();

        $uah = Currency::query()->where('name', 'UAH')->first();

        expect((float) $uah->exchange_rate)->toBe(0.024);
    });
});
