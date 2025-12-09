<?php

declare(strict_types=1);

namespace App\Traits\Calendar;

use Carbon\CarbonInterface;

trait CalculatesCalendarMetrics
{
    private const DURATION_INDEX_MULTIPLIER = 12;

    private const SECONDS_IN_HOUR = 3600;

    private const MINUTES_IN_HOUR = 60;

    private const START_INDEX_MULTIPLIER = 6;

    private const START_INDEX_OFFSET = 2;

    /**
     * Calculate seconds since midnight for a given datetime.
     */
    protected static function calculateSecondsSinceMidnight(CarbonInterface $dateTime): int
    {
        return ((int) $dateTime->format('H')) * self::SECONDS_IN_HOUR
            + ((int) $dateTime->format('i')) * 60
            + ((int) $dateTime->format('s'));
    }

    /**
     * Calculate duration index for calendar display (5-minute increments).
     */
    protected static function calculateDurationIndex(int $durationInMinutes): int
    {
        return (int) ($durationInMinutes * self::DURATION_INDEX_MULTIPLIER / self::MINUTES_IN_HOUR);
    }

    /**
     * Calculate start index for calendar display.
     */
    protected static function calculateStartIndex(int $secondsSinceMidnight): int
    {
        return (int) (($secondsSinceMidnight * self::START_INDEX_MULTIPLIER / self::SECONDS_IN_HOUR) + self::START_INDEX_OFFSET);
    }

    /**
     * Format datetime string with timezone abbreviation.
     */
    protected static function formatDateTimeWithTimezone(CarbonInterface $dateTime): string
    {
        $timezoneAbbreviation = $dateTime->format('T');

        return $dateTime->format('Y-m-d').'"'.$timezoneAbbreviation.'"'.$dateTime->format('H:i:s');
    }
}
