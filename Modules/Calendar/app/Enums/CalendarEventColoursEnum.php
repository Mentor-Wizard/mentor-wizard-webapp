<?php

declare(strict_types=1);

namespace Modules\Calendar\Enums;

use RuntimeException;

enum CalendarEventColoursEnum: string
{
    case BLUE = 'blue';
    case GREEN = 'green';
    case RED = 'red';
    case ORANGE = 'orange';
    case YELLOW = 'yellow';
    case PURPLE = 'purple';
    case PINK = 'pink';
    case GRAY = 'gray';

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

    public static function randomValue(): string
    {
        $values = self::values();
        throw_if($values === [], RuntimeException::class,
            'No calendar event colours are defined.');

        $randomIndex = random_int(0, count($values) - 1);

        return $values[$randomIndex];
    }
}
