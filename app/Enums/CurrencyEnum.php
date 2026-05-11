<?php

declare(strict_types=1);

namespace App\Enums;

enum CurrencyEnum: string
{
    case UAH = '₴';
    case USD = '$';
    case EUR = '€';
    case GBP = '£';

    /**
     * @return array<int, string>
     */
    public static function names(): array
    {
        /** @var array<int, string> $names */
        $names = array_column(self::cases(), 'name');

        return $names;
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        /** @var array<int, string> $values */
        $values = array_column(self::cases(), 'value');

        return $values;
    }

    public function exchangeRate(): float
    {
        return match ($this) {
            self::USD => 1.0,
            self::EUR => 1.08,
            self::GBP => 1.27,
            self::UAH => 0.024,
        };
    }
}
