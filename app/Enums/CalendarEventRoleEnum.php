<?php

declare(strict_types=1);

namespace App\Enums;

enum CalendarEventRoleEnum: string
{
    case HOST = 'Host';
    case COHOST = 'Co-Host';
    case MENTI = 'Menti';
    case MODERATOR = 'Moderator';
    case PARTICIPANT = 'Participant';

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
