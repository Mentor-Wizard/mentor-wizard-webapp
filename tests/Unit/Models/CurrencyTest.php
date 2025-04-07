<?php

use App\Enums\CurrencyEnum;
use App\Models\Currency;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Support\Str;

mutates(Currency::class);

describe('Currency Model', function () {
    it('can create a new currency', function () {
        $data = [
            'name' => CurrencyEnum::EUR->name,
            'slug' => Str::slug(CurrencyEnum::EUR->name),
            'symbol' => CurrencyEnum::EUR->value
        ];

        $currency = Currency::create($data);

        expect($currency)->toBeInstanceOf(Currency::class)
            ->and($currency->name)->toBe('EUR')
            ->and($currency->slug)->toBe('eur')
            ->and($currency->symbol)->toBe('€');
    });

    it('has fillable attributes', function () {
        $fillableAttributes = new Currency()->getFillable();

        expect($fillableAttributes)->toEqual(['name', 'slug', 'symbol']);
    });

    it('generates a slug automatically if not provided', function () {
        $currency = Currency::create([
            'name' => 'EUR',
            'symbol' => '€'
        ]);

        expect($currency->slug)->toBeNull();
    });

    it('can use factory to create currency', function () {
        $currency = Currency::factory()->create();

        expect($currency)->toBeInstanceOf(Currency::class)
            ->and($currency->getKey())->not()->toBeNull();
    });

    it('has precisely defined fillable attributes and mass assignment works correctly', function () {
        Currency::create(['extra_field' => 'test']);
    })->throws(MassAssignmentException::class);
});
