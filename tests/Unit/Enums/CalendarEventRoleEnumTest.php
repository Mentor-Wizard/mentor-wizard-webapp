<?php

declare(strict_types=1);

use App\Enums\CalendarEventRoleEnum;

mutates(CalendarEventRoleEnum::class);

describe('CalendarEventRoleEnum', function (): void {
    it('returns all names and values', function (): void {
        $names = CalendarEventRoleEnum::names();
        $values = CalendarEventRoleEnum::values();

        $caseNames = array_map(fn (CalendarEventRoleEnum $case) => $case->name, CalendarEventRoleEnum::cases());
        $caseValues = array_map(fn (CalendarEventRoleEnum $case) => $case->value, CalendarEventRoleEnum::cases());

        expect($names)->toEqual($caseNames)
            ->and($values)->toEqual($caseValues)
            ->and(array_unique($names))->toHaveSameSize($names)
            ->and(array_unique($values))->toHaveSameSize($values)
            ->and($names)->each->not->toBeEmpty()
            ->and($values)->each->not->toBeEmpty();
    });

    it('contains required roles', function (): void {
        expect(CalendarEventRoleEnum::values())
            ->toContain('Host')
            ->toContain('Co-Host')
            ->toContain('Menti')
            ->toContain('Moderator')
            ->toContain('Participant');
    });
});
