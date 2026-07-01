<?php

declare(strict_types=1);

use App\Enums\CalendarEventColoursEnum;

mutates(CalendarEventColoursEnum::class);

describe('CalendarEventColoursEnum', function (): void {
    it('returns all names and values', function (): void {
        $names = CalendarEventColoursEnum::names();
        $values = CalendarEventColoursEnum::values();

        $caseNames = array_map(fn (CalendarEventColoursEnum $caseName) => $caseName->name, CalendarEventColoursEnum::cases());
        $caseValues = array_map(fn (CalendarEventColoursEnum $caseValue) => $caseValue->value, CalendarEventColoursEnum::cases());

        expect($names)->toEqual($caseNames)
            ->and($values)->toEqual($caseValues)
            ->and(array_unique($names))->toHaveSameSize($names)
            ->and(array_unique($values))->toHaveSameSize($values)
            ->and($names)->each->not->toBeEmpty()
            ->and($values)->each->not->toBeEmpty();
    });

    it('randomValue only ever returns defined values and can reach every value', function (): void {
        $values = CalendarEventColoursEnum::values();
        $seen = [];

        for ($iteration = 0; $iteration < 1000; $iteration++) {
            $random = CalendarEventColoursEnum::randomValue();

            expect($values)->toContain($random);

            $seen[$random] = true;
        }

        $reachedValues = array_keys($seen);
        sort($reachedValues);

        $expectedValues = $values;
        sort($expectedValues);

        expect($reachedValues)->toEqual($expectedValues);
    });
});
