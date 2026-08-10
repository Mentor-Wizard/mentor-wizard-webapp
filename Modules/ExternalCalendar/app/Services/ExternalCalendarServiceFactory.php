<?php

declare(strict_types=1);

namespace Modules\ExternalCalendar\Services;

use Modules\ExternalCalendar\Enums\CalendarProviderEnum;
use Modules\ExternalCalendar\Services\Contracts\ExternalCalendarServiceInterface;

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
