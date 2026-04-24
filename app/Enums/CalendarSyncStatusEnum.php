<?php

declare(strict_types=1);

namespace App\Enums;

enum CalendarSyncStatusEnum: string
{
    case ACTIVE = 'active';
    case PENDING = 'pending';
    case ERROR = 'error';
    case DISCONNECTED = 'disconnected';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
