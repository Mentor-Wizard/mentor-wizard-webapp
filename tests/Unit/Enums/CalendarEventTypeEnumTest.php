<?php

declare(strict_types=1);

use App\Enums\CalendarEventTypeEnum;

mutates(CalendarEventTypeEnum::class);

describe('CalendarEventTypeEnum', function (): void {
    it('returns all names and values', function (): void {
        $names = CalendarEventTypeEnum::names();
        $values = CalendarEventTypeEnum::values();

        $caseNames = array_map(fn (CalendarEventTypeEnum $case) => $case->name, CalendarEventTypeEnum::cases());
        $caseValues = array_map(fn (CalendarEventTypeEnum $case) => $case->value, CalendarEventTypeEnum::cases());

        expect($names)->toEqual($caseNames)
            ->and($values)->toEqual($caseValues)
            ->and(array_unique($names))->toHaveSameSize($names)
            ->and(array_unique($values))->toHaveSameSize($values)
            ->and($names)->each->not->toBeEmpty()
            ->and($values)->each->not->toBeEmpty();
    });

    it('contains required types', function (): void {
        expect(CalendarEventTypeEnum::values())
            ->toContain('Individual')
            ->toContain('Group');
    });
});
