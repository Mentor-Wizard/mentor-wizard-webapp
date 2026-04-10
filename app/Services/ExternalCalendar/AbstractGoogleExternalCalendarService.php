<?php

declare(strict_types=1);

namespace App\Services\ExternalCalendar;

use App\Enums\CalendarProviderEnum;
use App\Enums\CalendarSyncStatusEnum;
use App\Models\CalendarEvent;
use App\Models\User;
use App\Models\UserCalendarIntegration;
use Illuminate\Support\Facades\Http;
use RuntimeException;

abstract class AbstractGoogleExternalCalendarService implements ExternalCalendarServiceInterface
{
    private const string AUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';

    private const string TOKEN_URL = 'https://oauth2.googleapis.com/token';

    private const string CALENDAR_LIST_URL = 'https://www.googleapis.com/calendar/v3/users/me/calendarList';

    private const string CALENDAR_EVENTS_URL = 'https://www.googleapis.com/calendar/v3/calendars/{calendarId}/events';

    private const string CALENDAR_EVENT_URL = 'https://www.googleapis.com/calendar/v3/calendars/{calendarId}/events/{eventId}';

    private const string CALENDAR_SCOPE = 'https://www.googleapis.com/auth/calendar';

    abstract protected function provider(): CalendarProviderEnum;

    abstract protected function clientId(?UserCalendarIntegration $integration = null): ?string;

    abstract protected function clientSecret(?UserCalendarIntegration $integration = null): string;

    abstract public function saveCredentials(User $user, ?string $clientId, ?string $clientSecret): UserCalendarIntegration;

    abstract public function callbackUrl(): string;

    public function buildOAuthUrl(?string $clientId, string $state): string
    {
        return self::AUTH_URL.'?'.http_build_query([
            'client_id'     => $clientId ?? $this->clientId(),
            'redirect_uri'  => $this->callbackUrl(),
            'response_type' => 'code',
            'scope'         => self::CALENDAR_SCOPE,
            'access_type'   => 'offline',
            'prompt'        => 'consent',
            'state'         => $state,
        ]);
    }

    public function handleCallback(User $user, string $code): UserCalendarIntegration
    {
        $integration = UserCalendarIntegration::query()
            ->where('user_id', $user->getKey())
            ->where('provider', $this->provider())
            ->firstOrFail();

        $response = Http::asForm()->post(self::TOKEN_URL, [
            'code'          => $code,
            'client_id'     => $this->clientId($integration),
            'client_secret' => $this->clientSecret($integration),
            'redirect_uri'  => $this->callbackUrl(),
            'grant_type'    => 'authorization_code',
        ]);

        $data = $response->json();

        $integration->update([
            'access_token'     => $data['access_token'] ?? null,
            'refresh_token'    => $data['refresh_token'] ?? null,
            'token_expires_at' => isset($data['expires_in'])
                ? now()->addSeconds((int) $data['expires_in'])
                : null,
            'sync_status'      => CalendarSyncStatusEnum::Pending,
        ]);

        return $integration->refresh();
    }

    public function selectCalendar(User $user, string $calendarId, string $calendarName): UserCalendarIntegration
    {
        $integration = UserCalendarIntegration::query()
            ->where('user_id', $user->getKey())
            ->where('provider', $this->provider())
            ->firstOrFail();

        $integration->update([
            'calendar_id'        => $calendarId,
            'calendar_name'      => $calendarName,
            'sync_status'        => CalendarSyncStatusEnum::Active,
            'needs_reauth'       => false,
            'last_error_message' => null,
        ]);

        return $integration->refresh();
    }

    /**
     * @return array{success: bool, calendars: list<array{id: string, name: string, primary: bool}>, error: string|null}
     */
    public function fetchCalendars(UserCalendarIntegration $integration): array
    {
        $response = Http::withToken((string) $integration->access_token)->get(self::CALENDAR_LIST_URL);

        if ($response->successful()) {
            /** @var list<array{id: string, name: string, primary: bool}> $calendars */
            $calendars = array_values(array_map(
                static fn (array $item): array => [
                    'id'      => (string) $item['id'],
                    'name'    => isset($item['summary']) ? (string) $item['summary'] : (string) $item['id'],
                    'primary' => (bool) ($item['primary'] ?? false),
                ],
                $response->json('items', [])
            ));

            return ['success' => true, 'calendars' => $calendars, 'error' => null];
        }

        $error = $response->json('error.message') ?? 'Unable to fetch calendars.';

        return ['success' => false, 'calendars' => [], 'error' => $error];
    }

    public function createEvent(CalendarEvent $event, UserCalendarIntegration $integration): string
    {
        $integration = $this->refreshTokenIfExpired($integration);

        $calendarId = $integration->calendar_id ?? 'primary';

        $url = str_replace('{calendarId}', urlencode($calendarId), self::CALENDAR_EVENTS_URL);

        $response = Http::withToken((string) $integration->access_token)->post($url, [
            'summary'     => $event->title,
            'description' => $event->description,
            'start'       => [
                'dateTime' => $event->start_date_time->toRfc3339String(),
                'timeZone' => 'UTC',
            ],
            'end'         => [
                'dateTime' => $event->end_date_time->toRfc3339String(),
                'timeZone' => 'UTC',
            ],
        ]);

        if (! $response->successful()) {
            $error = $response->json('error.message') ?? 'Unknown error';

            throw new RuntimeException('Google Calendar event creation failed: '.$error);
        }

        return (string) $response->json('id');
    }

    public function updateEvent(CalendarEvent $event, UserCalendarIntegration $integration, string $externalEventId): void
    {
        $integration = $this->refreshTokenIfExpired($integration);

        $calendarId = $integration->calendar_id ?? 'primary';

        $url = str_replace(
            ['{calendarId}', '{eventId}'],
            [urlencode($calendarId), urlencode($externalEventId)],
            self::CALENDAR_EVENT_URL,
        );

        $response = Http::withToken((string) $integration->access_token)->patch($url, [
            'summary'     => $event->title,
            'description' => $event->description,
            'start'       => [
                'dateTime' => $event->start_date_time->toRfc3339String(),
                'timeZone' => 'UTC',
            ],
            'end'         => [
                'dateTime' => $event->end_date_time->toRfc3339String(),
                'timeZone' => 'UTC',
            ],
        ]);

        if (! $response->successful()) {
            $error = $response->json('error.message') ?? 'Unknown error';

            throw new RuntimeException('Google Calendar event update failed: '.$error);
        }
    }

    public function deleteEvent(UserCalendarIntegration $integration, string $externalEventId): void
    {
        $integration = $this->refreshTokenIfExpired($integration);

        $calendarId = $integration->calendar_id ?? 'primary';

        $url = str_replace(
            ['{calendarId}', '{eventId}'],
            [urlencode($calendarId), urlencode($externalEventId)],
            self::CALENDAR_EVENT_URL,
        );

        $response = Http::withToken((string) $integration->access_token)->delete($url);

        // 404 means the event was already removed externally — treat as success
        if (! $response->successful() && $response->status() !== 404) {
            $error = $response->json('error.message') ?? 'Unknown error';

            throw new RuntimeException('Google Calendar event deletion failed: '.$error);
        }
    }

    private function refreshTokenIfExpired(UserCalendarIntegration $integration): UserCalendarIntegration
    {
        if (! $integration->isTokenExpired()) {
            return $integration;
        }

        if ($integration->refresh_token === null) {
            $integration->update([
                'needs_reauth'       => true,
                'sync_status'        => CalendarSyncStatusEnum::Error,
                'last_error_message' => 'Access token expired and no refresh token is available. Please reconnect.',
            ]);

            throw new RuntimeException('Google Calendar token expired and no refresh token available.');
        }

        $response = Http::asForm()->post(self::TOKEN_URL, [
            'client_id'     => $this->clientId($integration),
            'client_secret' => $this->clientSecret($integration),
            'refresh_token' => $integration->refresh_token,
            'grant_type'    => 'refresh_token',
        ]);

        if (! $response->successful()) {
            $integration->update([
                'needs_reauth'       => true,
                'sync_status'        => CalendarSyncStatusEnum::Error,
                'last_error_message' => 'Token refresh failed: '.($response->json('error_description') ?? $response->json('error') ?? 'Unknown error'),
            ]);

            throw new RuntimeException('Google Calendar token refresh failed.');
        }

        $data = $response->json();

        $integration->update([
            'access_token'     => $data['access_token'],
            'token_expires_at' => isset($data['expires_in'])
                ? now()->addSeconds((int) $data['expires_in'])
                : null,
        ]);

        return $integration->refresh();
    }
}
