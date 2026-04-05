<?php

declare(strict_types=1);

namespace App\Enums;

use App\Services\ExternalCalendar\ExternalCalendarServiceInterface;
use App\Services\ExternalCalendar\GoogleExternalCalendarService;

enum CalendarProviderEnum: string
{
    case Google = 'google';
    case Outlook = 'outlook';
    case Apple = 'apple';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function isValid(string $provider): bool
    {
        return in_array($provider, self::values(), true);
    }

    /**
     * @return class-string<ExternalCalendarServiceInterface>
     */
    public function serviceClass(): string
    {
        return match ($this) {
            self::Google  => GoogleExternalCalendarService::class,
            self::Outlook => GoogleExternalCalendarService::class, // placeholder until implemented
            self::Apple   => GoogleExternalCalendarService::class, // placeholder until implemented
        };
    }
}
