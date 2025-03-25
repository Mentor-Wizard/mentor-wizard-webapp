<?php

declare(strict_types=1);

namespace App\Enums;

enum CurrencyEnum: string
{
    case UAH = '₴';
    case USD = '$';
    case EUR = '€';
    case GBP = '£';

    public static function names(): array
    {
        return array_column(self::cases(), 'name');
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
