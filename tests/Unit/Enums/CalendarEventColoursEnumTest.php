<?php

declare(strict_types=1);

use App\Enums\CalendarEventColoursEnum;

mutates(CalendarEventColoursEnum::class);

describe('CalendarEventColoursEnum', function (): void {
    it('returns all names and values and a valid random value', function (): void {
        $names = CalendarEventColoursEnum::names();
        $values = CalendarEventColoursEnum::values();
        $random = CalendarEventColoursEnum::randomValue();

        $caseNames = array_map(fn (CalendarEventColoursEnum $caseName) => $caseName->name, CalendarEventColoursEnum::cases());
        $caseValues = array_map(fn (CalendarEventColoursEnum $caseValue) => $caseValue->value, CalendarEventColoursEnum::cases());

        expect($names)->toEqual($caseNames)
            ->and($values)->toEqual($caseValues)
            ->and(in_array($random, $values, true))->toBeTrue()
            ->and(array_unique($names))->toHaveSameSize($names)
            ->and(array_unique($values))->toHaveSameSize($values)
            ->and($names)->each->not->toBeEmpty()
            ->and($values)->each->not->toBeEmpty();
    });
});
