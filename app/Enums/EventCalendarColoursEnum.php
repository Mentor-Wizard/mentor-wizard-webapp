<?php

declare(strict_types=1);

namespace App\Enums;

enum EventCalendarColoursEnum: string
{
    case BLUE = 'blue';
    case GREEN = 'green';
    case RED = 'red';
    case ORANGE = 'orange';
    case YELLOW = 'yellow';
    case PURPLE = 'purple';
    case PINK = 'pink';
    case GRAY = 'gray';

    public static function names(): array
    {
        return array_column(self::cases(), 'name');
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function randomValue(): string
    {
        return self::values()[array_rand(self::values())];
    }
}
