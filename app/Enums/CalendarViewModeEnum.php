<?php

declare(strict_types=1);

namespace App\Enums;

enum CalendarViewModeEnum: string
{
    case DAY = 'Day view';
    case WEEK = 'Week view';
    case MONTH = 'Month view';

    public static function names(): array
    {
        return array_column(self::cases(), 'name');
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
