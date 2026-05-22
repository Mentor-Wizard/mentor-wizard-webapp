<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\CurrencyEnum;

mutates(CurrencyEnum::class);

describe('CurrencyEnum', function (): void {
    it('returns all names and values correctly', function (): void {
        $names = CurrencyEnum::names();
        $values = CurrencyEnum::values();

        $caseNames = array_map(fn (CurrencyEnum $case) => $case->name, CurrencyEnum::cases());
        $caseValues = array_map(fn (CurrencyEnum $case) => $case->value, CurrencyEnum::cases());

        expect($names)->toEqual($caseNames)
            ->and($values)->toEqual($caseValues)
            ->and(count($names))->toBe(4);
    });

    it('has the expected symbols', function (): void {
        expect(CurrencyEnum::UAH->value)->toBe('₴')
            ->and(CurrencyEnum::USD->value)->toBe('$')
            ->and(CurrencyEnum::EUR->value)->toBe('€')
            ->and(CurrencyEnum::GBP->value)->toBe('£');
    });
});
