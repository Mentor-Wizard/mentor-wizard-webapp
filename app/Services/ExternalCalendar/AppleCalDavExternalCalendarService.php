<?php

declare(strict_types=1);

namespace App\Services\ExternalCalendar;

use App\DTO\ExternalCalendar\ExternalCalendarEventData;
use App\Enums\CalendarProviderEnum;
use App\Enums\CalendarSyncStatusEnum;
use App\Models\CalendarEvent;
use App\Models\User;
use App\Models\UserCalendarIntegration;
use App\Services\ExternalCalendar\Contracts\ExternalCalendarServiceInterface;
use App\Services\XmlTools\ExternalCalendar\CalDavCalendarListParser;
use App\Services\XmlTools\ExternalCalendar\CalDavPropfindParser;
use App\Services\XmlTools\ExternalCalendar\CalDavReportParser;
use App\Services\XmlTools\ExternalCalendar\IcsBuilder;
use App\Traits\ExternalCalendar\XmlAppleCalendarRequests;
use DateTimeInterface;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class AppleCalDavExternalCalendarService implements ExternalCalendarServiceInterface
{
    use XmlAppleCalendarRequests;

    private const string CALDAV_ROOT = 'https://caldav.icloud.com';

    private const string WELL_KNOWN_PATH = '/.well-known/caldav';

    /**
     * Store Apple ID (client_id) and App-Specific Password (client_secret).
     * No OAuth is involved — credentials are used directly for Basic Auth.
     */
    public function saveCredentials(User $user, ?string $clientId, ?string $clientSecret): UserCalendarIntegration
    {
        /** @var UserCalendarIntegration */
        return UserCalendarIntegration::query()->updateOrCreate(
            [
                'user_id'  => $user->getKey(),
                'provider' => CalendarProviderEnum::Apple,
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
                'sync_status'        => CalendarSyncStatusEnum::Pending,
                'last_error_message' => null,
            ]
        );
    }

    public function selectCalendar(User $user, string $calendarId, string $calendarName): UserCalendarIntegration
    {
        $integration = UserCalendarIntegration::query()
            ->where('user_id', $user->getKey())
            ->where('provider', CalendarProviderEnum::Apple)
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
     * Discovers the user's CalDAV calendar home and lists available calendars.
     *
     * @return array{success: bool, calendars: list<array{id: string, name: string, primary: bool}>, error: string|null}
     */
    public function fetchCalendars(UserCalendarIntegration $integration): array
    {
        $appleId = (string) $integration->client_id;
        $password = (string) $integration->client_secret;

        $home = $this->discoverCalendarHome($appleId, $password);

        if ($home === null) {
            return ['success' => false, 'calendars' => [],
                'error'       => 'Unable to discover CalDAV calendar home. Check your Apple ID and App-Specific Password.'];
        }

        return $this->listCalendars($appleId, $password, $home);
    }

    /**
     * Fetches events from the Apple CalDAV calendar within the given UTC time range.
     *
     * @return list<ExternalCalendarEventData>
     */
    public function fetchEvents(UserCalendarIntegration $integration, DateTimeInterface $from, DateTimeInterface $to): array
    {
        $appleId = (string) $integration->client_id;
        $password = (string) $integration->client_secret;
        $calendarId = (string) $integration->calendar_id;

        $fromStr = Date::instance($from)->timezone(config('app.timezone'))->format('Ymd\THis\Z');
        $toStr = Date::instance($to)->timezone(config('app.timezone'))->format('Ymd\THis\Z');

        $body = $this->calendarQueryReport($fromStr, $toStr);

        $response = Http::withBasicAuth($appleId, $password)
            ->withHeaders(['Depth' => '1', 'Content-Type' => 'text/xml'])
            ->withBody($body, 'text/xml')
            ->send('REPORT', $calendarId);

        if (! $response->successful()) {
            throw new RuntimeException('Apple CalDAV fetch events failed: HTTP '.$response->status());
        }

        return new CalDavReportParser(self::CALDAV_ROOT)->getParsedReport($response->body());
    }

    public function createEvent(CalendarEvent $event, UserCalendarIntegration $integration): string
    {
        $appleId = (string) $integration->client_id;
        $password = (string) $integration->client_secret;
        $calendarId = (string) $integration->calendar_id;
        $uid = Str::uuid()->toString();
        $eventUrl = mb_rtrim($calendarId, '/').'/'.$uid.'.ics';

        $response = Http::withBasicAuth($appleId, $password)
            ->withHeaders(['Content-Type' => 'text/calendar; charset=utf-8'])
            ->withBody(new IcsBuilder()->build($uid, $event), 'text/calendar')
            ->put($eventUrl);

        if (! $response->successful()) {
            throw new RuntimeException('Apple CalDAV event creation failed: HTTP '.$response->status());
        }

        return $eventUrl;
    }

    public function updateEvent(CalendarEvent $event, UserCalendarIntegration $integration, string $externalEventId): void
    {
        $appleId = (string) $integration->client_id;
        $password = (string) $integration->client_secret;
        $uid = $this->uidFromUrl($externalEventId);

        $response = Http::withBasicAuth($appleId, $password)
            ->withHeaders(['Content-Type' => 'text/calendar; charset=utf-8'])
            ->withBody(new IcsBuilder()->build($uid, $event), 'text/calendar')
            ->put($externalEventId);

        if (! $response->successful()) {
            throw new RuntimeException('Apple CalDAV event update failed: HTTP '.$response->status());
        }
    }

    public function deleteEvent(UserCalendarIntegration $integration, string $externalEventId): void
    {
        $appleId = (string) $integration->client_id;
        $password = (string) $integration->client_secret;

        $response = Http::withBasicAuth($appleId, $password)->delete($externalEventId);

        // 404 means the event was already removed externally — treat as success
        if (! $response->successful() && $response->status() !== 404) {
            throw new RuntimeException('Apple CalDAV event deletion failed: HTTP '.$response->status());
        }
    }

    /**
     * Follows the well-known redirect to locate the authenticated user's calendar home URL.
     */
    private function discoverCalendarHome(string $appleId, string $password): ?string
    {
        // Step 1: resolve the well-known URL — Apple returns a 301 redirect.
        // Guzzle converts non-GET redirects to GET, so we disable auto-redirect
        // and re-issue the PROPFIND manually to the Location URL.
        $wellKnownUrl = self::CALDAV_ROOT.self::WELL_KNOWN_PATH;

        $response = Http::withBasicAuth($appleId, $password)
            ->withoutRedirecting()
            ->withHeaders(['Depth' => '0', 'Content-Type' => 'text/xml'])
            ->withBody($this->propfindCurrentUserPrincipal(), 'text/xml')
            ->send('PROPFIND', $wellKnownUrl);

        if ($response->redirect()) {
            $redirectUrl = $response->header('Location');

            if ($redirectUrl === '') {
                return null;
            }

            $response = Http::withBasicAuth($appleId, $password)
                ->withHeaders(['Depth' => '0', 'Content-Type' => 'text/xml'])
                ->withBody($this->propfindCurrentUserPrincipal(), 'text/xml')
                ->send('PROPFIND', $redirectUrl);
        }

        if (! $response->successful()) {
            return null;
        }

        $principalUrl = new CalDavPropfindParser()->extractValue($response->body(), 'current-user-principal', 'href');

        if ($principalUrl === null) {
            return null;
        }

        $principalUrl = $this->absoluteUrl($principalUrl);

        // Step 2: find the calendar-home-set from the principal
        $response = Http::withBasicAuth($appleId, $password)
            ->withHeaders(['Depth' => '0', 'Content-Type' => 'text/xml'])
            ->withBody($this->propfindCalendarHome(), 'text/xml')
            ->send('PROPFIND', $principalUrl);

        if (! $response->successful()) {
            return null;
        }

        $homeUrl = new CalDavPropfindParser()->extractValue($response->body(), 'calendar-home-set', 'href');

        return $homeUrl !== null ? $this->absoluteUrl($homeUrl) : null;
    }

    /**
     * Lists all calendars under the given calendar home URL.
     *
     * @return array{success: bool, calendars: list<array{id: string, name: string, primary: bool}>, error: string|null}
     */
    private function listCalendars(string $appleId, string $password, string $calendarHomeUrl): array
    {
        $response = Http::withBasicAuth($appleId, $password)
            ->withHeaders(['Depth' => '1', 'Content-Type' => 'text/xml'])
            ->withBody($this->propfindCalendarList(), 'text/xml')
            ->send('PROPFIND', $calendarHomeUrl);

        if (! $response->successful()) {
            return [
                'success'   => false,
                'calendars' => [],
                'error'     => 'Unable to list calendars: HTTP '.$response->status(),
            ];
        }

        return [
            'success'   => true,
            'calendars' => new CalDavCalendarListParser(self::CALDAV_ROOT)
                ->getParsedCalendarList($response->body()), 'error' => null,
        ];
    }

    private function absoluteUrl(string $path): string
    {
        if (str_starts_with($path, 'http')) {
            return $path;
        }

        return self::CALDAV_ROOT.'/'.mb_ltrim($path, '/');
    }

    private function uidFromUrl(string $eventUrl): string
    {
        return pathinfo($eventUrl, PATHINFO_FILENAME);
    }
}
