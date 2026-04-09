<?php

declare(strict_types=1);

namespace App\Enums;

use App\Services\ExternalCalendar\AppleCalDavExternalCalendarService;
use App\Services\ExternalCalendar\ExternalCalendarServiceInterface;
use App\Services\ExternalCalendar\GoogleAppExternalCalendarService;
use App\Services\ExternalCalendar\GoogleExternalCalendarService;
use App\Services\ExternalCalendar\OutlookExternalCalendarService;

enum CalendarProviderEnum: string
{
    /** App-level Google OAuth (shared credentials from env). */
    case Google = 'google';

    /** Per-user Google OAuth (each user supplies their own client_id/secret). */
    case GooglePersonalApp = 'google_personal_app';

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
    public function getService(): string
    {
        return match ($this) {
            self::Google            => GoogleAppExternalCalendarService::class,
            self::GooglePersonalApp => GoogleExternalCalendarService::class,
            self::Outlook           => OutlookExternalCalendarService::class,
            self::Apple             => AppleCalDavExternalCalendarService::class,
        };
    }

    /**
     * Returns true when this provider uses app-level credentials from config
     * rather than per-user client_id / client_secret.
     */
    public function usesAppCredentials(): bool
    {
        return match ($this) {
            self::Google, self::Outlook => true,
            default                     => false,
        };
    }

    /**
     * Returns true for CalDAV-based providers that use direct credential
     * submission instead of an OAuth redirect + callback flow.
     */
    public function isCalDav(): bool
    {
        return $this === self::Apple;
    }
}
