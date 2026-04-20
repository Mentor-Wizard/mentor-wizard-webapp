<?php

declare(strict_types=1);

namespace App\Services\ExternalCalendar;

use App\Enums\CalendarProviderEnum;
use App\Services\ExternalCalendar\Contracts\ExternalCalendarServiceInterface;

class ExternalCalendarServiceFactory
{
    public function __construct(
        private readonly GoogleAppExternalCalendarService $googleApp,
        private readonly GoogleExternalCalendarService $googlePersonalApp,
        private readonly OutlookExternalCalendarService $outlook,
        private readonly AppleCalDavExternalCalendarService $apple,
    ) {}

    public function for(CalendarProviderEnum $provider): ExternalCalendarServiceInterface
    {
        return match ($provider) {
            CalendarProviderEnum::Google            => $this->googleApp,
            CalendarProviderEnum::GooglePersonalApp => $this->googlePersonalApp,
            CalendarProviderEnum::Outlook           => $this->outlook,
            CalendarProviderEnum::Apple             => $this->apple,
        };
    }
}
