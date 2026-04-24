<?php

declare(strict_types=1);

namespace App\Services\ExternalCalendar;

use App\Enums\CalendarProviderEnum;
use App\Enums\CalendarSyncStatusEnum;
use App\Models\User;
use App\Models\UserCalendarIntegration;

class GoogleAppExternalCalendarService extends AbstractGoogleExternalCalendarService
{
    public function callbackUrl(): string
    {
        return route('external-calendar.connect.callback', ['provider' => CalendarProviderEnum::GOOGLE->value]);
    }

    public function saveCredentials(User $user, ?string $clientId, ?string $clientSecret): UserCalendarIntegration
    {
        /** @var UserCalendarIntegration */
        // For google app - no need to save client id and secret, since tokens are received using already provided initially refresh token
        return UserCalendarIntegration::query()->updateOrCreate(
            [
                'user_id'  => $user->getKey(),
                'provider' => CalendarProviderEnum::GOOGLE,
            ],
            [
                'client_id'          => null,
                'client_secret'      => null,
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
        return CalendarProviderEnum::GOOGLE;
    }

    protected function clientId(?UserCalendarIntegration $integration = null): ?string
    {
        /** @var ?string */
        return config('calendar.google_client_id');
    }

    protected function clientSecret(?UserCalendarIntegration $integration = null): string
    {
        /** @var string */
        return config('calendar.google_client_secret');
    }
}
