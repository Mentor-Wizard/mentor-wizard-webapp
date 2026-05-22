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
            CalendarProviderEnum::GOOGLE              => $this->googleApp,
            CalendarProviderEnum::GOOGLE_PERSONAL_APP => $this->googlePersonalApp,
            CalendarProviderEnum::OUTLOOK             => $this->outlook,
            CalendarProviderEnum::APPLE               => $this->apple,
        };
    }
}
