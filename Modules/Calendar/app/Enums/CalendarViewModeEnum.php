<?php

declare(strict_types=1);

namespace Modules\Calendar\Enums;

enum CalendarViewModeEnum: string
{
    case DAY = 'Day view';
    case WEEK = 'Week view';
    case MONTH = 'Month view';

    /**
     * @return list<string>
     */
    public static function names(): array
    {
        return array_column(self::cases(), 'name');
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
