<?php

declare(strict_types=1);

namespace Modules\ExternalCalendar\Services;

use App\Models\User;
use DateTimeInterface;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Http;
use Modules\Calendar\Models\CalendarEvent;
use Modules\ExternalCalendar\DTO\ExternalCalendarEventData;
use Modules\ExternalCalendar\Enums\CalendarProviderEnum;
use Modules\ExternalCalendar\Enums\CalendarSyncStatusEnum;
use Modules\ExternalCalendar\Models\UserCalendarIntegration;
use Modules\ExternalCalendar\Services\Contracts\OAuthCalendarServiceInterface;
use RuntimeException;

class OutlookExternalCalendarService implements OAuthCalendarServiceInterface
{
    private const string AUTH_URL = 'https://login.microsoftonline.com/common/oauth2/v2.0/authorize';

    private const string TOKEN_URL = 'https://login.microsoftonline.com/common/oauth2/v2.0/token';

    private const string CALENDAR_LIST_URL = 'https://graph.microsoft.com/v1.0/me/calendars';

    private const string CALENDAR_EVENTS_URL = 'https://graph.microsoft.com/v1.0/me/calendars/{calendarId}/events';

    private const string CALENDAR_VIEW_URL = 'https://graph.microsoft.com/v1.0/me/calendars/{calendarId}/calendarView';

    private const string CALENDAR_EVENT_URL = 'https://graph.microsoft.com/v1.0/me/calendars/{calendarId}/events/{eventId}';

    private const string CALENDAR_SCOPE = 'https://graph.microsoft.com/Calendars.ReadWrite offline_access';

    public function saveCredentials(User $user, ?string $clientId, ?string $clientSecret): UserCalendarIntegration
    {
        /** @var UserCalendarIntegration */
        // For Outlook/Azure app - no need to save client id and secret, since tokens are received using already provided initially refresh token
        return UserCalendarIntegration::query()->updateOrCreate(
            [
                'user_id'  => $user->getKey(),
                'provider' => CalendarProviderEnum::OUTLOOK,
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

    public function buildOAuthUrl(?string $clientId, string $state): string
    {
        return self::AUTH_URL.'?'.http_build_query([
            'client_id'     => $this->clientId(),
            'redirect_uri'  => $this->callbackUrl(),
            'response_type' => 'code',
            'scope'         => self::CALENDAR_SCOPE,
            'response_mode' => 'query',
            'state'         => $state,
        ]);
    }

    public function handleCallback(User $user, string $code): UserCalendarIntegration
    {
        $integration = UserCalendarIntegration::query()
            ->where('user_id', $user->getKey())
            ->where('provider', CalendarProviderEnum::OUTLOOK)
            ->firstOrFail();

        $response = Http::asForm()->post(self::TOKEN_URL, [
            'code'          => $code,
            'client_id'     => $this->clientId(),
            'client_secret' => $this->clientSecret(),
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
            'sync_status'      => CalendarSyncStatusEnum::PENDING,
        ]);

        return $integration->refresh();
    }

    public function selectCalendar(User $user, string $calendarId, string $calendarName): UserCalendarIntegration
    {
        $integration = UserCalendarIntegration::query()
            ->where('user_id', $user->getKey())
            ->where('provider', CalendarProviderEnum::OUTLOOK)
            ->firstOrFail();

        $integration->update([
            'calendar_id'        => $calendarId,
            'calendar_name'      => $calendarName,
            'sync_status'        => CalendarSyncStatusEnum::ACTIVE,
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
                    'name'    => isset($item['name']) ? (string) $item['name'] : (string) $item['id'],
                    'primary' => (bool) ($item['isDefaultCalendar'] ?? false),
                ],
                $response->json('value', [])
            ));

            return ['success' => true, 'calendars' => $calendars, 'error' => null];
        }

        $error = $response->json('error.message') ?? 'Unable to fetch calendars.';

        return ['success' => false, 'calendars' => [], 'error' => $error];
    }

    /**
     * @return list<ExternalCalendarEventData>
     */
    public function fetchEvents(UserCalendarIntegration $integration, DateTimeInterface $from, DateTimeInterface $to): array
    {
        $integration = $this->refreshTokenIfExpired($integration);

        $calendarId = $integration->calendar_id ?? 'me';

        // calendarView expands recurring events and respects the time-range filter correctly
        $url = str_replace('{calendarId}', urlencode($calendarId), self::CALENDAR_VIEW_URL);

        // MS Graph requires ISO 8601 without timezone suffix for calendarView parameters
        $fromFormatted = Date::instance($from)->utc()->format('Y-m-d\TH:i:s.0000000');
        $toFormatted = Date::instance($to)->utc()->format('Y-m-d\TH:i:s.0000000');

        $response = Http::withToken((string) $integration->access_token)
            ->withHeaders(['Prefer' => 'outlook.timezone="'.config('app.timezone').'"'])
            ->get($url, [
                'startDateTime' => $fromFormatted,
                'endDateTime'   => $toFormatted,
                '$select'       => 'id,subject,body,start,end,bodyPreview',
            ]);

        if (! $response->successful()) {
            $error = $response->json('error.message') ?? 'Unknown error';

            throw new RuntimeException('Outlook Calendar fetch events failed: '.$error);
        }

        return array_values(array_map(
            $this->mapOutlookEvent(...),
            $response->json('value', [])
        ));
    }

    public function createEvent(CalendarEvent $event, UserCalendarIntegration $integration): string
    {
        $integration = $this->refreshTokenIfExpired($integration);

        $calendarId = $integration->calendar_id ?? 'me';

        $url = str_replace('{calendarId}', urlencode($calendarId), self::CALENDAR_EVENTS_URL);

        $response = Http::withToken((string) $integration->access_token)->post($url, [
            'subject' => $event->title,
            'body'    => [
                'contentType' => 'text',
                'content'     => (string) $event->description,
            ],
            'start'   => [
                'dateTime' => $event->start_date_time->toIso8601String(),
                'timeZone' => config('app.timezone'),
            ],
            'end'     => [
                'dateTime' => $event->end_date_time->toIso8601String(),
                'timeZone' => config('app.timezone'),
            ],
        ]);

        if (! $response->successful()) {
            $error = $response->json('error.message') ?? 'Unknown error';

            throw new RuntimeException('Outlook Calendar event creation failed: '.$error);
        }

        return (string) $response->json('id');
    }

    public function updateEvent(CalendarEvent $event, UserCalendarIntegration $integration, string $externalEventId): void
    {
        $integration = $this->refreshTokenIfExpired($integration);

        $calendarId = $integration->calendar_id ?? 'me';

        $url = str_replace(
            ['{calendarId}', '{eventId}'],
            [urlencode($calendarId), urlencode($externalEventId)],
            self::CALENDAR_EVENT_URL,
        );

        $response = Http::withToken((string) $integration->access_token)->patch($url, [
            'subject' => $event->title,
            'body'    => [
                'contentType' => 'text',
                'content'     => (string) $event->description,
            ],
            'start'   => [
                'dateTime' => $event->start_date_time->toIso8601String(),
                'timeZone' => config('app.timezone'),
            ],
            'end'     => [
                'dateTime' => $event->end_date_time->toIso8601String(),
                'timeZone' => config('app.timezone'),
            ],
        ]);

        if (! $response->successful()) {
            $error = $response->json('error.message') ?? 'Unknown error';

            throw new RuntimeException('Outlook Calendar event update failed: '.$error);
        }
    }

    public function deleteEvent(UserCalendarIntegration $integration, string $externalEventId): void
    {
        $integration = $this->refreshTokenIfExpired($integration);

        $calendarId = $integration->calendar_id ?? 'me';

        $url = str_replace(
            ['{calendarId}', '{eventId}'],
            [urlencode($calendarId), urlencode($externalEventId)],
            self::CALENDAR_EVENT_URL,
        );

        $response = Http::withToken((string) $integration->access_token)->delete($url);

        // 404 means the event was already removed externally — treat as success
        if (! $response->successful() && $response->status() !== 404) {
            $error = $response->json('error.message') ?? 'Unknown error';

            throw new RuntimeException('Outlook Calendar event deletion failed: '.$error);
        }
    }

    public function callbackUrl(): string
    {
        return route('external-calendar.connect.callback', ['provider' => CalendarProviderEnum::OUTLOOK->value]);
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function mapOutlookEvent(array $item): ExternalCalendarEventData
    {
        $startData = $item['start'] ?? [];
        $endData = $item['end'] ?? [];

        // Because we sent `Prefer: outlook.timezone="UTC"`, the provider normalises to UTC
        $providerTimezone = (string) ($startData['timeZone'] ?? config('app.timezone'));

        $startUtc = Date::parse((string) $startData['dateTime'])->timezone(config('app.timezone'));
        $endUtc = Date::parse((string) $endData['dateTime'])->timezone(config('app.timezone'));

        $description = $item['body']['content'] ?? $item['bodyPreview'] ?? null;
        $description = $description !== null ? mb_trim((string) $description) : null;
        $description = ($description === '') ? null : $description;

        return new ExternalCalendarEventData(
            externalId: (string) $item['id'],
            title: (string) ($item['subject'] ?? ''),
            startUtc: $startUtc,
            endUtc: $endUtc,
            description: $description,
            providerTimezone: $providerTimezone,
        );
    }

    private function clientId(): string
    {
        /** @var string */
        return config('calendar.microsoft_client_id');
    }

    private function clientSecret(): string
    {
        /** @var string */
        return config('calendar.microsoft_client_secret');
    }

    private function refreshTokenIfExpired(UserCalendarIntegration $integration): UserCalendarIntegration
    {
        if (! $integration->isTokenExpired()) {
            return $integration;
        }

        if ($integration->refresh_token === null) {
            $integration->update([
                'needs_reauth'       => true,
                'sync_status'        => CalendarSyncStatusEnum::ERROR,
                'last_error_message' => 'Access token expired and no refresh token is available. Please reconnect.',
            ]);

            throw new RuntimeException('Outlook Calendar token expired and no refresh token available.');
        }

        $response = Http::asForm()->post(self::TOKEN_URL, [
            'client_id'     => $this->clientId(),
            'client_secret' => $this->clientSecret(),
            'refresh_token' => $integration->refresh_token,
            'grant_type'    => 'refresh_token',
        ]);

        if (! $response->successful()) {
            $integration->update([
                'needs_reauth'       => true,
                'sync_status'        => CalendarSyncStatusEnum::ERROR,
                'last_error_message' => 'Token refresh failed: '.($response->json('error_description') ?? $response->json('error') ?? 'Unknown error'),
            ]);

            throw new RuntimeException('Outlook Calendar token refresh failed.');
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
