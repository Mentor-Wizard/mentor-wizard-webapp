<?php

declare(strict_types=1);

namespace App\Enums;

enum CalendarEventRoleEnum: string
{
    case HOST = 'Host';
    case COHOST = 'Co-Host';
    case MENTI = 'Menti';
    case MODERATOR = 'Moderator';

    public static function names(): array
    {
        return array_column(self::cases(), 'name');
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
