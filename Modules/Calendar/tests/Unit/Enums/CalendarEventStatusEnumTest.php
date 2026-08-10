<?php

declare(strict_types=1);

use Modules\Calendar\Enums\CalendarEventStatusEnum;
use Modules\Calendar\Enums\CalendarViewModeEnum;

describe('CalendarEventStatusEnum', function (): void {
    it('returns all names and values', function (): void {
        $names = CalendarEventStatusEnum::names();
        $values = CalendarEventStatusEnum::values();

        $caseNames = array_map(fn (CalendarEventStatusEnum $caseName) => $caseName->name, CalendarEventStatusEnum::cases());
        $caseValues = array_map(fn (CalendarEventStatusEnum $caseValue) => $caseValue->value, CalendarEventStatusEnum::cases());

        expect($names)->toEqual($caseNames)
            ->and($values)->toEqual($caseValues)
            ->and(array_unique($names))->toHaveSameSize($names)
            ->and(array_unique($values))->toHaveSameSize($values)
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

describe('CalendarViewModeEnum', function (): void {
    it('returns all names and values', function (): void {
        $names = CalendarViewModeEnum::names();
        $values = CalendarViewModeEnum::values();

        $caseNames = array_map(fn (CalendarViewModeEnum $c) => $c->name, CalendarViewModeEnum::cases());
        $caseValues = array_map(fn (CalendarViewModeEnum $c) => $c->value, CalendarViewModeEnum::cases());

        expect($names)->toEqual($caseNames)
            ->and($values)->toEqual($caseValues)
            ->and(array_unique($names))->toHaveSameSize($names)
            ->and(array_unique($values))->toHaveSameSize($values)
            ->and($names)->each->not->toBeEmpty()
            ->and($values)->each->not->toBeEmpty();
    });

    it('contains the expected view labels', function (): void {
        expect(CalendarViewModeEnum::values())
            ->toContain('Day view')
            ->toContain('Week view')
            ->toContain('Month view');
    });
});
