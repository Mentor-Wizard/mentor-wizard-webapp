<?php

declare(strict_types=1);

namespace App\Support;

final class CurrencyConverter
{
    public static function toCents(float $amount): int
    {
        return (int) round($amount * 100);
    }

    public static function fromCents(int $cents): float
    {
        return $cents / 100;
    }
}
