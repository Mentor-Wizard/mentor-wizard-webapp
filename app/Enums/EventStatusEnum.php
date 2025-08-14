<?php

declare(strict_types=1);

namespace App\Enums;

enum EventStatusEnum: string
{
    case PENDING_MENTOR_CONFIRMATION = 'Pending Mentor Confirmation';
    case PENDING_PAYMENT = 'Pending Payment';
    case CONFIRMED = 'Confirmed';
    case FINISHED = 'Finished';
    case CANCELLED = 'Cancelled';

    public static function names(): array
    {
        return array_column(self::cases(), 'name');
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
