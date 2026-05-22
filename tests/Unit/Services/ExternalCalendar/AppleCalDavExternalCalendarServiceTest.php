<?php

declare(strict_types=1);

use App\Enums\CalendarProviderEnum;
use App\Enums\CalendarSyncStatusEnum;
use App\Models\CalendarEvent;
use App\Models\MentorProgram;
use App\Models\User;
use App\Models\UserCalendarIntegration;
use App\Services\ExternalCalendar\AppleCalDavExternalCalendarService;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Http;

mutates(AppleCalDavExternalCalendarService::class);

// Minimal CalDAV XML fixtures used across tests
function applePrincipalXml(): string
{
    return '<?xml version="1.0" encoding="UTF-8"?>'
        .'<D:multistatus xmlns:D="DAV:">'
        .'<D:response><D:propstat><D:prop>'
        .'<D:current-user-principal><D:href>/principals/users/alice/</D:href></D:current-user-principal>'
        .'</D:prop></D:propstat></D:response>'
        .'</D:multistatus>';
}

function appleCalendarHomeXml(): string
{
    return '<?xml version="1.0" encoding="UTF-8"?>'
        .'<D:multistatus xmlns:D="DAV:">'
        .'<D:response><D:propstat><D:prop>'
        .'<D:calendar-home-set><D:href>/calendars/alice/</D:href></D:calendar-home-set>'
        .'</D:prop></D:propstat></D:response>'
        .'</D:multistatus>';
}

function appleCalendarListXml(): string
{
    return '<?xml version="1.0" encoding="UTF-8"?>'
        .'<D:multistatus xmlns:D="DAV:" xmlns:C="urn:ietf:params:xml:ns:caldav">'
        .'<D:response>'
        .'<D:href>/calendars/alice/home/</D:href>'
        .'<D:propstat><D:prop>'
        .'<D:resourcetype><D:collection/><C:calendar/></D:resourcetype>'
        .'<D:displayname>My Calendar</D:displayname>'
        .'</D:prop></D:propstat>'
        .'</D:response>'
        .'</D:multistatus>';
}

function emptyMultistatus(): string
{
    return '<?xml version="1.0" encoding="UTF-8"?>'
        .'<D:multistatus xmlns:D="DAV:" xmlns:C="urn:ietf:params:xml:ns:caldav"></D:multistatus>';
}

describe('AppleCalDavExternalCalendarService', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        config(['calendar.encryption_key1' => base64_encode(random_bytes(32))]);

        $this->user = User::factory()->create();
        $this->service = new AppleCalDavExternalCalendarService;
    });

    describe('saveCredentials', function (): void {
        it('creates an Apple integration storing Apple ID and App-Specific Password', function (): void {
            $integration = $this->service->saveCredentials($this->user, 'user@icloud.com', 'xxxx-yyyy-zzzz');

            expect($integration->user_id)->toBe($this->user->getKey())
                ->and($integration->provider)->toBe(CalendarProviderEnum::APPLE)
                ->and($integration->sync_status)->toBe(CalendarSyncStatusEnum::PENDING)
                ->and($integration->access_token)->toBeNull()
                ->and($integration->needs_reauth)->toBeFalse();
        });

        it('updates an existing Apple integration', function (): void {
            $existing = UserCalendarIntegration::factory()->create([
                'user_id'  => $this->user->getKey(),
                'provider' => CalendarProviderEnum::APPLE,
            ]);

            $integration = $this->service->saveCredentials($this->user, 'new@icloud.com', 'new-password');

            expect($integration->getKey())->toBe($existing->getKey())
                ->and($integration->sync_status)->toBe(CalendarSyncStatusEnum::PENDING);
        });
    });

    describe('selectCalendar', function (): void {
        it('updates the integration with calendar URL and sets Active status', function (): void {
            $integration = UserCalendarIntegration::factory()->create([
                'user_id'  => $this->user->getKey(),
                'provider' => CalendarProviderEnum::APPLE,
            ]);

            $result = $this->service->selectCalendar(
                $this->user,
                'https://caldav.icloud.com/calendars/alice/home/',
                'My Calendar',
            );

            expect($result->getKey())->toBe($integration->getKey())
                ->and($result->calendar_id)->toBe('https://caldav.icloud.com/calendars/alice/home/')
                ->and($result->calendar_name)->toBe('My Calendar')
                ->and($result->sync_status)->toBe(CalendarSyncStatusEnum::ACTIVE)
                ->and($result->needs_reauth)->toBeFalse();
        });
    });

    describe('fetchCalendars', function (): void {
        it('discovers the calendar home and returns the calendar list on success', function (): void {
            $integration = UserCalendarIntegration::factory()->create([
                'user_id'   => $this->user->getKey(),
                'provider'  => CalendarProviderEnum::APPLE,
                'client_id' => 'user@icloud.com',
            ]);

            Http::fake([
                'https://caldav.icloud.com/.well-known/caldav' => Http::response(applePrincipalXml(), 207),
                'https://caldav.icloud.com/principals/*'       => Http::response(appleCalendarHomeXml(), 207),
                'https://caldav.icloud.com/*'                  => Http::response(appleCalendarListXml(), 207),
            ]);

            $result = $this->service->fetchCalendars($integration);

            expect($result['success'])->toBeTrue()
                ->and($result['error'])->toBeNull()
                ->and($result['calendars'])->not->toBeEmpty()
                ->and($result['calendars'][0]['name'])->toBe('My Calendar');
        });

        it('discovers the calendar home when well-known returns a 301 redirect', function (): void {
            $integration = UserCalendarIntegration::factory()->create([
                'user_id'   => $this->user->getKey(),
                'provider'  => CalendarProviderEnum::APPLE,
                'client_id' => 'user@icloud.com',
            ]);

            // .well-known/caldav redirects to the CalDAV root.
            // More-specific patterns (principals/*, calendars/*) take priority over the generic wildcard.
            Http::fake([
                'https://caldav.icloud.com/.well-known/caldav'  => Http::response('', 301, ['Location' => 'https://caldav.icloud.com/']),
                'https://caldav.icloud.com/principals/*'        => Http::response(appleCalendarHomeXml(), 207),
                'https://caldav.icloud.com/calendars/*'         => Http::response(appleCalendarListXml(), 207),
                'https://caldav.icloud.com/*'                   => Http::response(applePrincipalXml(), 207),
            ]);

            $result = $this->service->fetchCalendars($integration);

            expect($result['success'])->toBeTrue()
                ->and($result['error'])->toBeNull()
                ->and($result['calendars'])->not->toBeEmpty()
                ->and($result['calendars'][0]['name'])->toBe('My Calendar');
        });

        it('returns an error when the well-known PROPFIND fails', function (): void {
            $integration = UserCalendarIntegration::factory()->create([
                'user_id'  => $this->user->getKey(),
                'provider' => CalendarProviderEnum::APPLE,
            ]);

            Http::fake([
                'https://caldav.icloud.com/*' => Http::response('', 401),
            ]);

            $result = $this->service->fetchCalendars($integration);

            expect($result['success'])->toBeFalse()
                ->and($result['calendars'])->toBe([])
                ->and($result['error'])->toContain('Unable to discover CalDAV calendar home');
        });

        it('returns an error when the principal URL cannot be parsed from the response', function (): void {
            $integration = UserCalendarIntegration::factory()->create([
                'user_id'  => $this->user->getKey(),
                'provider' => CalendarProviderEnum::APPLE,
            ]);

            // Returns a valid 207 but XML has no current-user-principal element
            Http::fake([
                'https://caldav.icloud.com/*' => Http::response(emptyMultistatus(), 207),
            ]);

            $result = $this->service->fetchCalendars($integration);

            expect($result['success'])->toBeFalse()
                ->and($result['error'])->toContain('Unable to discover CalDAV calendar home');
        });

        it('returns an error when the calendar home PROPFIND fails', function (): void {
            $integration = UserCalendarIntegration::factory()->create([
                'user_id'  => $this->user->getKey(),
                'provider' => CalendarProviderEnum::APPLE,
            ]);

            Http::fake([
                'https://caldav.icloud.com/.well-known/caldav' => Http::response(applePrincipalXml(), 207),
                'https://caldav.icloud.com/principals/*'       => Http::response('', 500),
            ]);

            $result = $this->service->fetchCalendars($integration);

            expect($result['success'])->toBeFalse()
                ->and($result['error'])->toContain('Unable to discover CalDAV calendar home');
        });

        it('returns an error when the calendar list PROPFIND fails', function (): void {
            $integration = UserCalendarIntegration::factory()->create([
                'user_id'  => $this->user->getKey(),
                'provider' => CalendarProviderEnum::APPLE,
            ]);

            Http::fake([
                'https://caldav.icloud.com/.well-known/caldav' => Http::response(applePrincipalXml(), 207),
                'https://caldav.icloud.com/principals/*'       => Http::response(appleCalendarHomeXml(), 207),
                'https://caldav.icloud.com/*'                  => Http::response('', 403),
            ]);

            $result = $this->service->fetchCalendars($integration);

            expect($result['success'])->toBeFalse()
                ->and($result['error'])->toContain('Unable to list calendars');
        });
    });

    describe('fetchEvents', function (): void {
        it('returns an empty array when the REPORT response contains no events', function (): void {
            $integration = UserCalendarIntegration::factory()->create([
                'user_id'      => $this->user->getKey(),
                'provider'     => CalendarProviderEnum::APPLE,
                'calendar_id'  => 'https://caldav.icloud.com/calendars/alice/home/',
            ]);

            Http::fake([
                'https://caldav.icloud.com/*' => Http::response(emptyMultistatus(), 207),
            ]);

            $events = $this->service->fetchEvents(
                $integration,
                Date::parse('2026-04-18'),
                Date::parse('2026-04-19'),
            );

            expect($events)->toBeArray()->toBeEmpty();
        });

        it('throws RuntimeException when the REPORT request fails', function (): void {
            $integration = UserCalendarIntegration::factory()->create([
                'user_id'     => $this->user->getKey(),
                'provider'    => CalendarProviderEnum::APPLE,
                'calendar_id' => 'https://caldav.icloud.com/calendars/alice/home/',
            ]);

            Http::fake([
                'https://caldav.icloud.com/*' => Http::response('', 401),
            ]);

            expect(fn () => $this->service->fetchEvents(
                $integration,
                Date::parse('2026-04-18'),
                Date::parse('2026-04-19'),
            ))->toThrow(RuntimeException::class, 'Apple CalDAV fetch events failed: HTTP 401');
        });
    });

    describe('createEvent', function (): void {
        beforeEach(function (): void {
            $mentorProgram = MentorProgram::factory()->create(['mentor_id' => $this->user->getKey()]);
            $this->calendarEvent = CalendarEvent::factory()->create([
                'mentor_program_id' => $mentorProgram->getKey(),
                'start_date_time'   => Date::tomorrow()->setTime(10, 0),
                'end_date_time'     => Date::tomorrow()->setTime(11, 0),
            ]);
        });

        it('creates an ICS file via PUT and returns its URL', function (): void {
            $integration = UserCalendarIntegration::factory()->create([
                'user_id'     => $this->user->getKey(),
                'provider'    => CalendarProviderEnum::APPLE,
                'calendar_id' => 'https://caldav.icloud.com/calendars/alice/home/',
            ]);

            Http::fake([
                'https://caldav.icloud.com/*' => Http::response('', 201),
            ]);

            $url = $this->service->createEvent($this->calendarEvent, $integration);

            expect($url)
                ->toStartWith('https://caldav.icloud.com/calendars/alice/home/')
                ->toEndWith('.ics');
        });

        it('throws RuntimeException when event creation fails', function (): void {
            $integration = UserCalendarIntegration::factory()->create([
                'user_id'     => $this->user->getKey(),
                'provider'    => CalendarProviderEnum::APPLE,
                'calendar_id' => 'https://caldav.icloud.com/calendars/alice/home/',
            ]);

            Http::fake([
                'https://caldav.icloud.com/*' => Http::response('', 403),
            ]);

            expect(fn () => $this->service->createEvent($this->calendarEvent, $integration))
                ->toThrow(RuntimeException::class, 'Apple CalDAV event creation failed: HTTP 403');
        });
    });

    describe('updateEvent', function (): void {
        beforeEach(function (): void {
            $mentorProgram = MentorProgram::factory()->create(['mentor_id' => $this->user->getKey()]);
            $this->calendarEvent = CalendarEvent::factory()->create([
                'mentor_program_id' => $mentorProgram->getKey(),
                'start_date_time'   => Date::tomorrow()->setTime(10, 0),
                'end_date_time'     => Date::tomorrow()->setTime(11, 0),
            ]);
        });

        it('sends a PUT request to the event ICS URL', function (): void {
            $integration = UserCalendarIntegration::factory()->create([
                'user_id'  => $this->user->getKey(),
                'provider' => CalendarProviderEnum::APPLE,
            ]);

            $eventUrl = 'https://caldav.icloud.com/calendars/alice/home/some-uid.ics';

            Http::fake(['https://caldav.icloud.com/*' => Http::response('', 204)]);

            $this->service->updateEvent($this->calendarEvent, $integration, $eventUrl);

            Http::assertSent(fn ($req): bool => $req->method() === 'PUT'
                && $req->url() === $eventUrl);
        });

        it('throws RuntimeException when event update fails', function (): void {
            $integration = UserCalendarIntegration::factory()->create([
                'user_id'  => $this->user->getKey(),
                'provider' => CalendarProviderEnum::APPLE,
            ]);

            Http::fake(['https://caldav.icloud.com/*' => Http::response('', 500)]);

            expect(fn () => $this->service->updateEvent(
                $this->calendarEvent,
                $integration,
                'https://caldav.icloud.com/calendars/alice/home/evt.ics',
            ))->toThrow(RuntimeException::class, 'Apple CalDAV event update failed: HTTP 500');
        });
    });

    describe('deleteEvent', function (): void {
        it('sends a DELETE request and succeeds on 200', function (): void {
            $integration = UserCalendarIntegration::factory()->create([
                'user_id'  => $this->user->getKey(),
                'provider' => CalendarProviderEnum::APPLE,
            ]);

            $eventUrl = 'https://caldav.icloud.com/calendars/alice/home/evt-to-del.ics';

            Http::fake(['https://caldav.icloud.com/*' => Http::response('', 200)]);

            $this->service->deleteEvent($integration, $eventUrl);

            Http::assertSent(fn ($req): bool => $req->method() === 'DELETE'
                && $req->url() === $eventUrl);
        });

        it('does not throw when event is already deleted externally (404)', function (): void {
            $integration = UserCalendarIntegration::factory()->create([
                'user_id'  => $this->user->getKey(),
                'provider' => CalendarProviderEnum::APPLE,
            ]);

            Http::fake(['https://caldav.icloud.com/*' => Http::response('', 404)]);

            $this->service->deleteEvent($integration, 'https://caldav.icloud.com/calendars/alice/evt.ics');

            expect(true)->toBeTrue();
        });

        it('throws RuntimeException on non-404 failure', function (): void {
            $integration = UserCalendarIntegration::factory()->create([
                'user_id'  => $this->user->getKey(),
                'provider' => CalendarProviderEnum::APPLE,
            ]);

            Http::fake(['https://caldav.icloud.com/*' => Http::response('', 500)]);

            expect(fn () => $this->service->deleteEvent(
                $integration,
                'https://caldav.icloud.com/calendars/alice/evt.ics',
            ))->toThrow(RuntimeException::class, 'Apple CalDAV event deletion failed: HTTP 500');
        });
    });
});
