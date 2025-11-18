<?php

declare(strict_types=1);

namespace App\Enums;

enum UserScheduleRecordType: string
{
    case ALL_WORKING_DAYS = 'Working Day';
    case ODD_DAYS = 'Odd';
    case EVEN_DAYS = 'Even';
    case DAY_OFF = 'Day off';
    case WEEKEND = 'Weekend';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function isValid(string $driver): bool
    {
        return in_array($driver, self::values(), true);
    }
}
