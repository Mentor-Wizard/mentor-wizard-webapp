<?php

declare(strict_types=1);

namespace App\Services\ExternalCalendar;

use App\Enums\CalendarProviderEnum;
use App\Enums\CalendarSyncStatusEnum;
use App\Models\CalendarEvent;
use App\Models\User;
use App\Models\UserCalendarIntegration;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use LogicException;
use RuntimeException;

class AppleCalDavExternalCalendarService implements ExternalCalendarServiceInterface
{
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

    /** @throws LogicException — CalDAV does not use OAuth. */
    public function buildOAuthUrl(?string $clientId, string $state): string
    {
        throw new LogicException('Apple CalDAV does not support OAuth. Use the direct connect flow.');
    }

    /** @throws LogicException — CalDAV does not use OAuth callbacks. */
    public function handleCallback(User $user, string $code): UserCalendarIntegration
    {
        throw new LogicException('Apple CalDAV does not support OAuth callbacks. Use the direct connect flow.');
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
            return ['success' => false, 'calendars' => [], 'error' => 'Unable to discover CalDAV calendar home. Check your Apple ID and App-Specific Password.'];
        }

        return $this->listCalendars($appleId, $password, $home);
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
            ->withBody($this->buildIcs($uid, $event), 'text/calendar')
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
            ->withBody($this->buildIcs($uid, $event), 'text/calendar')
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

    public function callbackUrl(): string
    {
        throw new LogicException('Apple CalDAV does not have an OAuth callback URL.');
    }

    /**
     * Follows the well-known redirect to locate the authenticated user's calendar home URL.
     */
    private function discoverCalendarHome(string $appleId, string $password): ?string
    {
        // Step 1: follow .well-known redirect to the principal URL
        $response = Http::withBasicAuth($appleId, $password)
            ->withHeaders(['Depth' => '0', 'Content-Type' => 'text/xml'])
            ->withBody($this->propfindCurrentUserPrincipal(), 'text/xml')
            ->send('PROPFIND', self::CALDAV_ROOT.self::WELL_KNOWN_PATH);

        if (! $response->successful()) {
            return null;
        }

        $principalUrl = $this->extractXmlValue($response->body(), 'current-user-principal', 'href');

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

        $homeUrl = $this->extractXmlValue($response->body(), 'calendar-home-set', 'href');

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
            $error = 'Unable to list calendars: HTTP '.$response->status();

            return ['success' => false, 'calendars' => [], 'error' => $error];
        }

        $calendars = $this->parseCalendarList($response->body());

        return ['success' => true, 'calendars' => $calendars, 'error' => null];
    }

    private function propfindCurrentUserPrincipal(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>
<D:propfind xmlns:D="DAV:">
  <D:prop>
    <D:current-user-principal/>
  </D:prop>
</D:propfind>';
    }

    private function propfindCalendarHome(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>
<D:propfind xmlns:D="DAV:" xmlns:C="urn:ietf:params:xml:ns:caldav">
  <D:prop>
    <C:calendar-home-set/>
  </D:prop>
</D:propfind>';
    }

    private function propfindCalendarList(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>
<D:propfind xmlns:D="DAV:" xmlns:C="urn:ietf:params:xml:ns:caldav" xmlns:CS="http://calendarserver.org/ns/">
  <D:prop>
    <D:resourcetype/>
    <D:displayname/>
    <CS:getctag/>
  </D:prop>
</D:propfind>';
    }

    /**
     * @return list<array{id: string, name: string, primary: bool}>
     */
    private function parseCalendarList(string $xml): array
    {
        $doc = new DOMDocument;

        if (! @$doc->loadXML($xml)) {
            return [];
        }

        $xpath = new DOMXPath($doc);
        $xpath->registerNamespace('D', 'DAV:');
        $xpath->registerNamespace('C', 'urn:ietf:params:xml:ns:caldav');

        $responses = $xpath->query('//D:response');

        if ($responses === false) {
            return [];
        }

        $calendars = [];

        foreach ($responses as $response) {
            $entry = $this->parseCalendarResponse($xpath, $response);

            if ($entry !== null) {
                $calendars[] = $entry;
            }
        }

        return $calendars;
    }

    /**
     * @return array{id: string, name: string, primary: bool}|null
     */
    private function parseCalendarResponse(DOMXPath $xpath, mixed $response): ?array
    {
        if (! $response instanceof DOMElement) {
            return null;
        }

        $calendarNodes = $xpath->query('.//C:calendar', $response);

        if ($calendarNodes === false || $calendarNodes->length === 0) {
            return null;
        }

        $hrefNodes = $xpath->query('D:href', $response);

        if ($hrefNodes === false) {
            return null;
        }

        $hrefNode = $hrefNodes->item(0);

        if (! $hrefNode instanceof DOMElement) {
            return null;
        }

        $nameNodes = $xpath->query('.//D:displayname', $response);
        $nameNode = $nameNodes !== false ? $nameNodes->item(0) : null;

        $href = $this->absoluteUrl($hrefNode->textContent);
        $name = $nameNode instanceof DOMElement && $nameNode->textContent !== ''
            ? $nameNode->textContent
            : basename(mb_rtrim($href, '/'));

        return [
            'id'      => $href,
            'name'    => $name,
            'primary' => str_contains($href, 'home') || str_contains(mb_strtolower($name), 'home'),
        ];
    }

    private function buildIcs(string $uid, CalendarEvent $event): string
    {
        $now = now()->format('Ymd\THis\Z');
        $start = $event->start_date_time->utc()->format('Ymd\THis\Z');
        $end = $event->end_date_time->utc()->format('Ymd\THis\Z');

        $description = $event->description !== null
            ? 'DESCRIPTION:'.str_replace(["\r\n", "\n", "\r"], '\\n', $event->description)."\r\n"
            : '';

        return "BEGIN:VCALENDAR\r\n"
            ."VERSION:2.0\r\n"
            ."PRODID:-//MentorWizard//EN\r\n"
            ."BEGIN:VEVENT\r\n"
            ."UID:{$uid}\r\n"
            ."DTSTAMP:{$now}\r\n"
            ."DTSTART:{$start}\r\n"
            ."DTEND:{$end}\r\n"
            ."SUMMARY:{$event->title}\r\n"
            .$description
            ."END:VEVENT\r\n"
            ."END:VCALENDAR\r\n";
    }

    /**
     * Extracts the text content of the first <href> child inside the given
     * element name from a CalDAV PROPFIND XML response.
     */
    private function extractXmlValue(string $xml, string $elementName, string $childElement): ?string
    {
        $doc = new DOMDocument;

        if (! @$doc->loadXML($xml)) {
            return null;
        }

        $xpath = new DOMXPath($doc);
        $xpath->registerNamespace('D', 'DAV:');
        $xpath->registerNamespace('C', 'urn:ietf:params:xml:ns:caldav');

        $queries = [
            sprintf('//%s/D:%s', $elementName, $childElement),
            sprintf('//D:%s/D:%s', $elementName, $childElement),
            sprintf('//C:%s/D:%s', $elementName, $childElement),
        ];

        foreach ($queries as $query) {
            $result = $xpath->query($query);

            if ($result === false) {
                continue;
            }

            $node = $result->item(0);

            if ($node instanceof DOMElement) {
                return mb_trim($node->textContent);
            }
        }

        return null;
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
