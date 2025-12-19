<?php

declare(strict_types=1);

namespace App\Enums;

use Illuminate\Support\Collection;

enum UserScheduleRecordType: string
{
    case WORKING_DAY = 'Working Day';
    case DAY_OFF = 'Day off';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function isValid(string $driver): bool
    {
        return in_array($driver, self::values(), true);
    }

    public static function getCollection(): Collection
    {
        return collect(self::cases())->map(fn ($type): array => [
            'value' => $type->value,
            'label' => $type->value,
        ]);
    }
}
