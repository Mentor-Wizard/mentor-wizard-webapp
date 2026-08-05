<?php

declare(strict_types=1);

namespace Modules\Calendar\Enums;

enum CalendarEventTypeEnum: string
{
    case INDIVIDUAL = 'Individual';
    case GROUP = 'Group';

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
