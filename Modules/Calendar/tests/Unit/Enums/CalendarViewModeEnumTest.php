<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use Modules\Calendar\Enums\CalendarViewModeEnum;

describe('CalendarViewModeEnum', function (): void {
    it('returns all names and values correctly', function (): void {
        $names = CalendarViewModeEnum::names();
        $values = CalendarViewModeEnum::values();

        $caseNames = array_map(fn (CalendarViewModeEnum $case) => $case->name, CalendarViewModeEnum::cases());
        $caseValues = array_map(fn (CalendarViewModeEnum $case) => $case->value, CalendarViewModeEnum::cases());

        expect($names)->toEqual($caseNames)
            ->and($values)->toEqual($caseValues)
            ->and(count($names))->toBe(3);
    });

    it('has the expected values', function (): void {
        expect(CalendarViewModeEnum::DAY->value)->toBe('Day view')
            ->and(CalendarViewModeEnum::WEEK->value)->toBe('Week view')
            ->and(CalendarViewModeEnum::MONTH->value)->toBe('Month view');
    });
});
