<?php

declare(strict_types=1);

namespace App\Support;

final class CurrencyConverter
{
    public static function toKopiyky(float $amount): int
    {
        return (int) round($amount * 100);
    }

    public static function fromKopiyky(int $kopiyky): float
    {
        return $kopiyky / 100;
    }
}
