<?php

declare(strict_types=1);

namespace App\Enums;

enum SocialiteDriverEnum: string
{
    case GOOGLE = 'google';
    case GITHUB = 'github';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        /** @var array<int, string> $values */
        $values = array_column(self::cases(), 'value');

        return $values;
    }

    public static function isValid(string $driver): bool
    {
        return in_array($driver, self::values(), true);
    }
}
