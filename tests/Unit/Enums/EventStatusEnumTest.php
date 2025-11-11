<?php

declare(strict_types=1);

use App\Enums\CalendarEventStatusEnum;

mutates(CalendarEventStatusEnum::class);

describe('CalendarEventStatusEnum', function (): void {
    it('returns all names and values', function (): void {
        $names = CalendarEventStatusEnum::names();
        $values = CalendarEventStatusEnum::values();

        $caseNames = array_map(fn (CalendarEventStatusEnum $caseName) => $caseName->name, CalendarEventStatusEnum::cases());
        $caseValues = array_map(fn (CalendarEventStatusEnum $caseValue) => $caseValue->value, CalendarEventStatusEnum::cases());

        expect($names)->toEqual($caseNames)
            ->and($values)->toEqual($caseValues)
            ->and(array_unique($names))->toHaveCount(count($names))
            ->and(array_unique($values))->toHaveCount(count($values))
            ->and($names)->each->not->toBeEmpty()
            ->and($values)->each->not->toBeEmpty();
    });

    it('contains required statuses', function (): void {
        expect(CalendarEventStatusEnum::values())
            ->toContain('Pending Mentor Confirmation')
            ->toContain('Pending Payment')
            ->toContain('Confirmed')
            ->toContain('Finished')
            ->toContain('Cancelled');
    });
});
