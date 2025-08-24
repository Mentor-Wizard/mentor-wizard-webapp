<?php

declare(strict_types=1);

use App\Enums\EventTypeEnum;

mutates(EventTypeEnum::class);

describe('EventTypeEnum', function (): void {
    it('returns all names and values', function (): void {
        $names = EventTypeEnum::names();
        $values = EventTypeEnum::values();

        $caseNames = array_map(fn (EventTypeEnum $case) => $case->name, EventTypeEnum::cases());
        $caseValues = array_map(fn (EventTypeEnum $case) => $case->value, EventTypeEnum::cases());

        expect($names)->toEqual($caseNames)
            ->and($values)->toEqual($caseValues)
            ->and(array_unique($names))->toHaveCount(count($names))
            ->and(array_unique($values))->toHaveCount(count($values))
            ->and($names)->each->not->toBeEmpty()
            ->and($values)->each->not->toBeEmpty();
    });

    it('contains required types', function (): void {
        expect(EventTypeEnum::values())
            ->toContain('Individual')
            ->toContain('Group');
    });
});
