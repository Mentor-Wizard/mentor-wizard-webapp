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
        return route('external-calendar.connect.callback', ['provider' => CalendarProviderEnum::Google->value]);
    }

    public function saveCredentials(User $user, ?string $clientId, ?string $clientSecret): UserCalendarIntegration
    {
        /** @var UserCalendarIntegration */
        return UserCalendarIntegration::query()->updateOrCreate(
            [
                'user_id'  => $user->getKey(),
                'provider' => CalendarProviderEnum::Google,
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
                'sync_status'        => CalendarSyncStatusEnum::Pending,
                'last_error_message' => null,
            ]
        );
    }

    protected function provider(): CalendarProviderEnum
    {
        return CalendarProviderEnum::Google;
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
