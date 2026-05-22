<?php

declare(strict_types=1);

namespace App\Services\ExternalCalendar;

use App\Enums\CalendarProviderEnum;
use App\Enums\CalendarSyncStatusEnum;
use App\Models\User;
use App\Models\UserCalendarIntegration;

class GoogleExternalCalendarService extends AbstractGoogleExternalCalendarService
{
    public function callbackUrl(): string
    {
        return route('external-calendar.connect.callback', ['provider' => CalendarProviderEnum::GOOGLE_PERSONAL_APP->value]);
    }

    public function saveCredentials(User $user, ?string $clientId, ?string $clientSecret): UserCalendarIntegration
    {
        /** @var UserCalendarIntegration */
        return $user->calendarIntegrations()->updateOrCreate(
            [
                'provider' => CalendarProviderEnum::GOOGLE_PERSONAL_APP,
            ],
            [
                'client_id'          => $clientId,
                'client_secret'      => $clientSecret,
                'access_token'       => null,
                'refresh_token'      => null,
                'token_expires_at'   => null,
                'calendar_id'        => null,
                'calendar_name'      => null,
                'needs_reauth'       => false,
                'sync_status'        => CalendarSyncStatusEnum::PENDING,
                'last_error_message' => null,
            ]
        );
    }

    protected function provider(): CalendarProviderEnum
    {
        return CalendarProviderEnum::GOOGLE_PERSONAL_APP;
    }

    protected function clientId(?UserCalendarIntegration $integration = null): ?string
    {
        return $integration?->client_id;
    }

    protected function clientSecret(?UserCalendarIntegration $integration = null): string
    {
        return (string) $integration?->client_secret;
    }
}
