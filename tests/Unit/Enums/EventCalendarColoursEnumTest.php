<?php

declare(strict_types=1);

use App\Enums\EventCalendarColoursEnum;

mutates(EventCalendarColoursEnum::class);

describe('EventCalendarColoursEnum', function (): void {
    it('returns all names and values and a valid random value', function (): void {
        $names = EventCalendarColoursEnum::names();
        $values = EventCalendarColoursEnum::values();
        $random = EventCalendarColoursEnum::randomValue();

        // Ensure names and values contain all enum cases
        $caseNames = array_map(fn ($c) => $c->name, EventCalendarColoursEnum::cases());
        $caseValues = array_map(fn ($c) => $c->value, EventCalendarColoursEnum::cases());

        expect($names)->toEqual($caseNames)
            ->and($values)->toEqual($caseValues)
            ->and(in_array($random, $values, true))->toBeTrue();

        // Basic invariants: unique and non-empty
        expect(array_unique($names))->toHaveCount(count($names))
            ->and(array_unique($values))->toHaveCount(count($values))
            ->and($names)->each->not->toBeEmpty()
            ->and($values)->each->not->toBeEmpty();
    });
});
