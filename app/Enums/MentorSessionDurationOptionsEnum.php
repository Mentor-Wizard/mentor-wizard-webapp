<?php

declare(strict_types=1);

namespace App\Enums;

enum MentorSessionDurationOptionsEnum: int
{
    case FIFTEEN_MINUTES = 15;
    case HALF_HOUR = 30;
    case FORTY_FIVE_MINUTES = 45;
    case HOUR = 60;
    case ONE_AND_HALF_HOURS = 90;
    case TWO_HOURS = 120;

    /**
     * @return list<string>
     */
    public static function names(): array
    {
        return array_column(self::cases(), 'name');
    }

    /**
     * @return list<int>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
