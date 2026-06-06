<?php

declare(strict_types=1);

use App\DTO\ExternalCalendar\ExternalCalendarEventData;
use App\Enums\CalendarProviderEnum;
use App\Enums\CalendarSyncStatusEnum;
use App\Models\CalendarEvent;
use App\Models\MentorProgram;
use App\Models\User;
use App\Models\UserCalendarIntegration;
use App\Services\ExternalCalendar\AppleCalDavExternalCalendarService;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Sleep;

/**
 * Apple iCloud CalDAV integration tests.
 *
 * Required env variables:
 *
 *   TEST_APPLE_ID            – Apple ID email address
 *   TEST_APPLE_APP_PASSWORD  – App-Specific Password generated at appleid.apple.com
 *   TEST_APPLE_CALENDAR_URL  – Full CalDAV URL of the target calendar
 *                              (e.g. https://p12-caldav.icloud.com/dav/principal/calendars/home/)
 *
 * Run with:
 *   php artisan test --testsuite=Integration --filter=Apple
 */
describe('Apple CalDAV Calendar Integration', function (): void {
    beforeEach(function (): void {
        $this->createdEventIds = [];

        if (! config('calendar.testing.apple.id')) {
            $this->markTestSkipped('Apple Calendar credentials not configured. Set TEST_APPLE_ID.');
        }

        config(['calendar.encryption_key1' => config('calendar.encryption_key1') ?: base64_encode(random_bytes(32))]);

        $this->seed(RoleSeeder::class);

        $this->user = User::factory()->create();

        $this->mentorProgram = MentorProgram::factory()->create([
            'mentor_id' => $this->user->getKey(),
        ]);

        $this->integration = UserCalendarIntegration::factory()->create([
            'user_id'          => $this->user->getKey(),
            'provider'         => CalendarProviderEnum::APPLE,
            'client_id'        => config('calendar.testing.apple.id'),
            'client_secret'    => config('calendar.testing.apple.app_password'),
            'calendar_id'      => config('calendar.testing.apple.calendar_url'),
            'sync_status'      => CalendarSyncStatusEnum::ACTIVE,
            'access_token'     => null,
            'refresh_token'    => null,
            'token_expires_at' => null,
        ]);

        $this->service = resolve(AppleCalDavExternalCalendarService::class);
    });

    afterEach(function (): void {
        foreach ($this->createdEventIds as $eventId) {
            try {
                $this->service->deleteEvent($this->integration->refresh(), $eventId);
            } catch (Throwable) {
                // ignore cleanup errors
            }
        }
    });

    // ──────────────────────────────────────────────────────────────────────────
    // Basic roundtrip
    // ──────────────────────────────────────────────────────────────────────────

    it('creates and fetches back an event with correct UTC times', function (): void {
        $start = Date::create(2026, 6, 15, 14, 0, 0, 'UTC');
        $end = Date::create(2026, 6, 15, 15, 0, 0, 'UTC');

        $calEvent = CalendarEvent::factory()->create([
            'title'             => 'Integration Test Event – Apple UTC roundtrip',
            'description'       => 'Created by automated test',
            'start_date_time'   => $start,
            'end_date_time'     => $end,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);

        $externalId = $this->service->createEvent($calEvent, $this->integration);
        $this->createdEventIds[] = $externalId;

        // Apple CalDAV propagation may have a small delay; wait briefly
        Sleep::sleep(1);

        $fetched = $this->service->fetchEvents(
            $this->integration->refresh(),
            $start->copy()->subDay(),
            $end->copy()->addDay(),
        );

        $match = collect($fetched)->firstWhere('externalId', $externalId);

        expect($match)->not->toBeNull()
            ->and($match->title)->toBe('Integration Test Event – Apple UTC roundtrip')
            ->and($match->startUtc->toIso8601String())->toBe($start->toIso8601String())
            ->and($match->endUtc->toIso8601String())->toBe($end->toIso8601String());
    });

    it('returns ExternalCalendarEventData instances', function (): void {
        $start = Date::create(2026, 8, 10, 10, 0, 0, 'UTC');
        $end = $start->copy()->addHour();

        $calEvent = CalendarEvent::factory()->create([
            'title'             => 'Apple DTO type check',
            'start_date_time'   => $start,
            'end_date_time'     => $end,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);

        $externalId = $this->service->createEvent($calEvent, $this->integration);
        $this->createdEventIds[] = $externalId;
        Sleep::sleep(1);

        $fetched = $this->service->fetchEvents(
            $this->integration->refresh(),
            $start->copy()->subMinutes(5),
            $end->copy()->addMinutes(5),
        );

        $match = collect($fetched)->firstWhere('externalId', $externalId);

        expect($match)->toBeInstanceOf(ExternalCalendarEventData::class);
    });

    it('preserves description text through CalDAV ICS encoding', function (): void {
        $start = Date::create(2026, 6, 20, 14, 0, 0, 'UTC');
        $end = $start->copy()->addHour();

        $description = "Line one\nLine two, with comma; and semicolon\\backslash";

        $calEvent = CalendarEvent::factory()->create([
            'title'             => 'Apple description encoding test',
            'description'       => $description,
            'start_date_time'   => $start,
            'end_date_time'     => $end,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);

        $externalId = $this->service->createEvent($calEvent, $this->integration);
        $this->createdEventIds[] = $externalId;
        Sleep::sleep(1);

        $fetched = $this->service->fetchEvents(
            $this->integration->refresh(),
            $start->copy()->subMinutes(5),
            $end->copy()->addMinutes(5),
        );

        $match = collect($fetched)->firstWhere('externalId', $externalId);

        expect($match)->not->toBeNull()
            ->and($match->description)->toBe($description);
    });

    // ──────────────────────────────────────────────────────────────────────────
    // Timezone edge cases
    // ──────────────────────────────────────────────────────────────────────────

    it('correctly roundtrips an event at UTC midnight', function (): void {
        $start = Date::create(2026, 9, 1, 0, 0, 0, 'UTC');
        $end = Date::create(2026, 9, 1, 1, 0, 0, 'UTC');

        $calEvent = CalendarEvent::factory()->create([
            'title'             => 'Apple – midnight UTC event',
            'start_date_time'   => $start,
            'end_date_time'     => $end,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);

        $externalId = $this->service->createEvent($calEvent, $this->integration);
        $this->createdEventIds[] = $externalId;
        Sleep::sleep(1);

        $fetched = $this->service->fetchEvents(
            $this->integration->refresh(),
            $start->copy()->subMinutes(5),
            $end->copy()->addMinutes(5),
        );

        $match = collect($fetched)->firstWhere('externalId', $externalId);

        expect($match)->not->toBeNull()
            ->and($match->startUtc->toIso8601String())->toBe($start->toIso8601String())
            ->and($match->endUtc->toIso8601String())->toBe($end->toIso8601String());
    });

    it('correctly roundtrips an event just before US spring-forward (2026-03-08)', function (): void {
        $start = Date::create(2026, 3, 8, 6, 30, 0, 'UTC'); // 01:30 EST
        $end = Date::create(2026, 3, 8, 7, 0, 0, 'UTC');

        $calEvent = CalendarEvent::factory()->create([
            'title'             => 'Apple – US DST before spring-forward',
            'start_date_time'   => $start,
            'end_date_time'     => $end,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);

        $externalId = $this->service->createEvent($calEvent, $this->integration);
        $this->createdEventIds[] = $externalId;
        Sleep::sleep(1);

        $fetched = $this->service->fetchEvents(
            $this->integration->refresh(),
            $start->copy()->subMinutes(5),
            $end->copy()->addMinutes(5),
        );

        $match = collect($fetched)->firstWhere('externalId', $externalId);

        expect($match)->not->toBeNull()
            ->and($match->startUtc->toIso8601String())->toBe($start->toIso8601String())
            ->and($match->endUtc->toIso8601String())->toBe($end->toIso8601String());
    });

    it('correctly roundtrips an event just after US spring-forward (2026-03-08)', function (): void {
        $start = Date::create(2026, 3, 8, 7, 30, 0, 'UTC'); // 03:30 EDT
        $end = Date::create(2026, 3, 8, 8, 0, 0, 'UTC');

        $calEvent = CalendarEvent::factory()->create([
            'title'             => 'Apple – US DST after spring-forward',
            'start_date_time'   => $start,
            'end_date_time'     => $end,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);

        $externalId = $this->service->createEvent($calEvent, $this->integration);
        $this->createdEventIds[] = $externalId;
        Sleep::sleep(1);

        $fetched = $this->service->fetchEvents(
            $this->integration->refresh(),
            $start->copy()->subMinutes(5),
            $end->copy()->addMinutes(5),
        );

        $match = collect($fetched)->firstWhere('externalId', $externalId);

        expect($match)->not->toBeNull()
            ->and($match->startUtc->toIso8601String())->toBe($start->toIso8601String())
            ->and($match->endUtc->toIso8601String())->toBe($end->toIso8601String());
    });

    it('correctly roundtrips an event during US fall-back (2026-11-01)', function (): void {
        $start = Date::create(2026, 11, 1, 5, 0, 0, 'UTC'); // 01:00 EDT
        $end = Date::create(2026, 11, 1, 6, 0, 0, 'UTC'); // 02:00 EDT / 01:00 EST (transition moment)

        $calEvent = CalendarEvent::factory()->create([
            'title'             => 'Apple – US DST fall-back',
            'start_date_time'   => $start,
            'end_date_time'     => $end,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);

        $externalId = $this->service->createEvent($calEvent, $this->integration);
        $this->createdEventIds[] = $externalId;
        Sleep::sleep(1);

        $fetched = $this->service->fetchEvents(
            $this->integration->refresh(),
            $start->copy()->subMinutes(5),
            $end->copy()->addMinutes(5),
        );

        $match = collect($fetched)->firstWhere('externalId', $externalId);

        expect($match)->not->toBeNull()
            ->and($match->startUtc->toIso8601String())->toBe($start->toIso8601String())
            ->and($match->endUtc->toIso8601String())->toBe($end->toIso8601String());
    });

    it('correctly handles a cross-midnight UTC event', function (): void {
        $start = Date::create(2026, 6, 15, 23, 0, 0, 'UTC');
        $end = Date::create(2026, 6, 16, 1, 0, 0, 'UTC');

        $calEvent = CalendarEvent::factory()->create([
            'title'             => 'Apple – cross-midnight event',
            'start_date_time'   => $start,
            'end_date_time'     => $end,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);

        $externalId = $this->service->createEvent($calEvent, $this->integration);
        $this->createdEventIds[] = $externalId;
        Sleep::sleep(1);

        $fetched = $this->service->fetchEvents(
            $this->integration->refresh(),
            $start->copy()->subMinutes(5),
            $end->copy()->addMinutes(5),
        );

        $match = collect($fetched)->firstWhere('externalId', $externalId);

        expect($match)->not->toBeNull()
            ->and($match->startUtc->toIso8601String())->toBe($start->toIso8601String())
            ->and($match->endUtc->toIso8601String())->toBe($end->toIso8601String());
    });

    it('does not include events outside the requested range', function (): void {
        $inRange = Date::create(2026, 10, 5, 10, 0, 0, 'UTC');
        $outOfRange = Date::create(2026, 10, 5, 20, 0, 0, 'UTC');

        $inCalEvent = CalendarEvent::factory()->create([
            'title'             => 'Apple – in-range event',
            'start_date_time'   => $inRange,
            'end_date_time'     => $inRange->copy()->addHour(),
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);
        $outCalEvent = CalendarEvent::factory()->create([
            'title'             => 'Apple – out-of-range event',
            'start_date_time'   => $outOfRange,
            'end_date_time'     => $outOfRange->copy()->addHour(),
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);

        $inId = $this->service->createEvent($inCalEvent, $this->integration);
        $outId = $this->service->createEvent($outCalEvent, $this->integration);
        $this->createdEventIds[] = $inId;
        $this->createdEventIds[] = $outId;
        Sleep::sleep(1);

        $fetched = $this->service->fetchEvents(
            $this->integration->refresh(),
            $inRange->copy()->subMinutes(5),
            $inRange->copy()->addHour()->addMinutes(5),
        );

        $fetchedIds = collect($fetched)->pluck('externalId')->all();

        expect($fetchedIds)->toContain($inId)->not->toContain($outId);
    });
});
