<?php

declare(strict_types=1);

namespace App\Enums;

enum CalendarSyncStatusEnum: string
{
    case Active = 'active';
    case Pending = 'pending';
    case Error = 'error';
    case Disconnected = 'disconnected';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
