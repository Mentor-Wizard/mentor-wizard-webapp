<?php

declare(strict_types=1);

namespace App\Enums;

enum SocialiteDriver: string
{
    case GOOGLE = 'google';
    case GITHUB = 'github';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function isValid(string $driver): bool
    {
        return in_array($driver, self::values(), true);
    }
}
