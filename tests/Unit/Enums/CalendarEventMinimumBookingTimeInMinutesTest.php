<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\CalendarEventMinimumBookingTimeInMinutes;

mutates(CalendarEventMinimumBookingTimeInMinutes::class);

describe('CalendarEventMinimumBookingTimeInMinutes', function (): void {
    it('has correct integer values', function (): void {
        expect(CalendarEventMinimumBookingTimeInMinutes::HALF_HOUR->value)->toBe(30)
            ->and(CalendarEventMinimumBookingTimeInMinutes::HOUR->value)->toBe(60)
            ->and(CalendarEventMinimumBookingTimeInMinutes::TWO_HOURS->value)->toBe(120)
            ->and(CalendarEventMinimumBookingTimeInMinutes::SIX_HOURS->value)->toBe(360)
            ->and(CalendarEventMinimumBookingTimeInMinutes::TWELVE_HOURS->value)->toBe(720)
            ->and(CalendarEventMinimumBookingTimeInMinutes::DAY->value)->toBe(1440)
            ->and(CalendarEventMinimumBookingTimeInMinutes::TWO_DAYS->value)->toBe(2880)
            ->and(CalendarEventMinimumBookingTimeInMinutes::WEEK->value)->toBe(10080);
    });

    it('has the correct number of cases', function (): void {
        expect(CalendarEventMinimumBookingTimeInMinutes::cases())->toHaveCount(8);
    });
});
