<?php

declare(strict_types=1);

namespace App\Enums;

enum EventTypeEnum: string
{
    case INDIVIDUAL = 'Individual';
    case GROUP = 'Group';

    public static function names(): array
    {
        return array_column(self::cases(), 'name');
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
