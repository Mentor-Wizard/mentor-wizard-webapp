<?php

declare(strict_types=1);

namespace App\Services\ExternalCalendar;

use App\Enums\CalendarProviderEnum;
use App\Models\User;
use App\Models\UserCalendarIntegration;

class ExternalCalendarSynchronizationService
{
    public function saveCredentialsAndBuildOAuthUrl(User $user, CalendarProviderEnum $provider, string $clientId, string $clientSecret): string
    {
        $service = $this->resolveService($provider);
        $service->saveCredentials($user, $clientId, $clientSecret);

        $state = encrypt(json_encode(['user_id' => $user->getKey(), 'provider' => $provider->value]));

        return $service->buildOAuthUrl($clientId, $state);
    }

    public function handleCallback(User $user, CalendarProviderEnum $provider, string $code): UserCalendarIntegration
    {
        return $this->resolveService($provider)->handleCallback($user, $code);
    }

    /**
     * @return array{success: bool, calendars: list<array{id: string, name: string, primary: bool}>, error: string|null}
     */
    public function fetchCalendars(User $user, CalendarProviderEnum $provider): array
    {
        $integration = UserCalendarIntegration::query()
            ->where('user_id', $user->getKey())
            ->where('provider', $provider)
            ->firstOrFail();

        return $this->resolveService($provider)->fetchCalendars((string) $integration->access_token);
    }

    public function selectCalendar(User $user, CalendarProviderEnum $provider, string $calendarId, string $calendarName): UserCalendarIntegration
    {
        return $this->resolveService($provider)->selectCalendar($user, $calendarId, $calendarName);
    }

    public function disconnect(User $user, CalendarProviderEnum $provider): void
    {
        UserCalendarIntegration::query()
            ->where('user_id', $user->getKey())
            ->where('provider', $provider)
            ->delete();
    }

    private function resolveService(CalendarProviderEnum $provider): ExternalCalendarServiceInterface
    {
        return app($provider->serviceClass());
    }
}
