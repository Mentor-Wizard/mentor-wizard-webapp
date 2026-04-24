<?php

declare(strict_types=1);

use App\DTO\ExternalCalendar\ExternalCalendarEventData;
use App\Enums\CalendarProviderEnum;
use App\Enums\CalendarSyncStatusEnum;
use App\Models\CalendarEvent;
use App\Models\MentorProgram;
use App\Models\User;
use App\Models\UserCalendarIntegration;
use App\Services\ExternalCalendar\GoogleExternalCalendarService;
use Carbon\CarbonImmutable;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Date;

/**
 * Google Calendar integration tests.
 *
 * Required env variables (set in .env.testing or export before running):
 *
 *   TEST_GOOGLE_ACCESS_TOKEN      – valid OAuth2 access token
 *   TEST_GOOGLE_REFRESH_TOKEN     – OAuth2 refresh token
 *   TEST_GOOGLE_CLIENT_ID         – OAuth2 client ID (personal-app flow)
 *   TEST_GOOGLE_CLIENT_SECRET     – OAuth2 client secret (personal-app flow)
 *   TEST_GOOGLE_CALENDAR_ID       – calendar ID to use (e.g. "primary" or a full calendar ID)
 *
 * Run with:
 *   php artisan test --testsuite=Integration --filter=Google
 */
describe('Google Calendar Integration', function (): void {
    beforeEach(function (): void {
        $this->createdEventIds = [];

        if (! config('calendar.testing.google.access_token')) {
            $this->markTestSkipped('Google Calendar credentials not configured. Set TEST_GOOGLE_ACCESS_TOKEN.');
        }

        config(['calendar.encryption_key1' => config('calendar.encryption_key1') ?: base64_encode(random_bytes(32))]);

        $this->seed(RoleSeeder::class);

        $this->user = User::factory()->create();

        $this->mentorProgram = MentorProgram::factory()->create([
            'mentor_id' => $this->user->getKey(),
        ]);

        $this->integration = UserCalendarIntegration::factory()->create([
            'user_id'          => $this->user->getKey(),
            'provider'         => CalendarProviderEnum::GOOGLE_PERSONAL_APP,
            'access_token'     => config('calendar.testing.google.access_token'),
            'refresh_token'    => config('calendar.testing.google.refresh_token'),
            'client_id'        => config('calendar.testing.google.client_id'),
            'client_secret'    => config('calendar.testing.google.client_secret'),
            'calendar_id'      => config('calendar.testing.google.calendar_id'),
            'sync_status'      => CalendarSyncStatusEnum::ACTIVE,
            'token_expires_at' => now()->subMinute(),
        ]);

        $this->service = resolve(GoogleExternalCalendarService::class);
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

        $event = CalendarEvent::factory()->create([
            'title'             => 'Integration Test Event – UTC roundtrip',
            'description'       => 'Created by automated test',
            'start_date_time'   => $start,
            'end_date_time'     => $end,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);

        $externalId = $this->service->createEvent($event, $this->integration);
        $this->createdEventIds[] = $externalId;

        $fetched = $this->service->fetchEvents(
            $this->integration->refresh(),
            $start->copy()->subMinutes(5),
            $end->copy()->addMinutes(5),
        );

        $match = collect($fetched)->firstWhere('externalId', $externalId);

        expect($match)->not->toBeNull()
            ->and($match->title)->toBe('Integration Test Event – UTC roundtrip')
            ->and($match->startUtc->toIso8601String())->toBe($start->toIso8601String())
            ->and($match->endUtc->toIso8601String())->toBe($end->toIso8601String());
    });

    it('fetches nothing when no events exist in the requested range', function (): void {
        // Use a date range far in the past with no events
        $from = Date::create(2000, 1, 1, 0, 0, 0, 'UTC');
        $to = Date::create(2000, 1, 1, 1, 0, 0, 'UTC');

        $fetched = $this->service->fetchEvents($this->integration->refresh(), $from, $to);

        expect($fetched)->toBeArray()->toBeEmpty();
    });

    it('fetches multiple events within a range', function (): void {
        $base = Date::create(2026, 7, 20, 9, 0, 0, 'UTC');

        $events = [];
        for ($i = 0; $i < 3; $i++) {
            $start = $base->copy()->addHours($i * 2);
            $end = $start->copy()->addHour();

            $calEvent = CalendarEvent::factory()->create([
                'title'             => 'Multi-fetch test event '.$i,
                'start_date_time'   => $start,
                'end_date_time'     => $end,
                'mentor_program_id' => $this->mentorProgram->getKey(),
            ]);

            $externalId = $this->service->createEvent($calEvent, $this->integration);
            $this->createdEventIds[] = $externalId;
            $events[] = ['id' => $externalId, 'start' => $start, 'end' => $end];
        }

        $fetched = $this->service->fetchEvents(
            $this->integration->refresh(),
            $base->copy()->subMinutes(5),
            $base->copy()->addHours(6)->addMinutes(5),
        );

        $fetchedIds = collect($fetched)->pluck('externalId')->all();

        foreach ($events as $e) {
            expect($fetchedIds)->toContain($e['id']);
        }
    });

    it('returns ExternalCalendarEventData instances', function (): void {
        $start = Date::create(2026, 8, 10, 10, 0, 0, 'UTC');
        $end = $start->copy()->addHour();

        $calEvent = CalendarEvent::factory()->create([
            'title'             => 'DTO type check',
            'start_date_time'   => $start,
            'end_date_time'     => $end,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);

        $externalId = $this->service->createEvent($calEvent, $this->integration);
        $this->createdEventIds[] = $externalId;

        $fetched = $this->service->fetchEvents(
            $this->integration->refresh(),
            $start->copy()->subMinutes(5),
            $end->copy()->addMinutes(5),
        );

        $match = collect($fetched)->firstWhere('externalId', $externalId);

        expect($match)->toBeInstanceOf(ExternalCalendarEventData::class)
            ->and($match->startUtc)->toBeInstanceOf(CarbonImmutable::class)
            ->and($match->endUtc)->toBeInstanceOf(CarbonImmutable::class);
    });

    // ──────────────────────────────────────────────────────────────────────────
    // Timezone edge cases
    // ──────────────────────────────────────────────────────────────────────────

    it('correctly roundtrips an event at UTC midnight', function (): void {
        $start = Date::create(2026, 9, 1, 0, 0, 0, 'UTC'); // midnight UTC
        $end = Date::create(2026, 9, 1, 1, 0, 0, 'UTC');

        $calEvent = CalendarEvent::factory()->create([
            'title'             => 'Midnight UTC event',
            'start_date_time'   => $start,
            'end_date_time'     => $end,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);

        $externalId = $this->service->createEvent($calEvent, $this->integration);
        $this->createdEventIds[] = $externalId;

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
        // In America/New_York, clocks spring forward at 2026-03-08 02:00 EST → 03:00 EDT
        // 01:30 EST = 06:30 UTC (before transition)
        $start = Date::create(2026, 3, 8, 6, 30, 0, 'UTC');
        $end = Date::create(2026, 3, 8, 7, 0, 0, 'UTC');

        $calEvent = CalendarEvent::factory()->create([
            'title'             => 'US DST spring-forward – before transition',
            'start_date_time'   => $start,
            'end_date_time'     => $end,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);

        $externalId = $this->service->createEvent($calEvent, $this->integration);
        $this->createdEventIds[] = $externalId;

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
        // 03:30 EDT = 07:30 UTC (after clocks spring forward)
        $start = Date::create(2026, 3, 8, 7, 30, 0, 'UTC');
        $end = Date::create(2026, 3, 8, 8, 0, 0, 'UTC');

        $calEvent = CalendarEvent::factory()->create([
            'title'             => 'US DST spring-forward – after transition',
            'start_date_time'   => $start,
            'end_date_time'     => $end,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);

        $externalId = $this->service->createEvent($calEvent, $this->integration);
        $this->createdEventIds[] = $externalId;

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

    it('correctly roundtrips an event straddling US fall-back (2026-11-01)', function (): void {
        // America/New_York falls back at 2026-11-01 02:00 EDT → 01:00 EST
        // Event: 01:00–02:00 EDT (05:00–06:00 UTC) – in the "first" 01:xx hour before fallback
        $start = Date::create(2026, 11, 1, 5, 0, 0, 'UTC');
        $end = Date::create(2026, 11, 1, 6, 0, 0, 'UTC');

        $calEvent = CalendarEvent::factory()->create([
            'title'             => 'US DST fall-back – straddle event',
            'start_date_time'   => $start,
            'end_date_time'     => $end,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);

        $externalId = $this->service->createEvent($calEvent, $this->integration);
        $this->createdEventIds[] = $externalId;

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

    it('correctly roundtrips an event during EU spring-forward (2026-03-29)', function (): void {
        // Europe/Kyiv (EET, UTC+2 winter / EEST, UTC+3 summer) springs forward at 03:00
        // 02:30 EET = 00:30 UTC (before transition — this time exists)
        $start = Date::create(2026, 3, 29, 0, 30, 0, 'UTC');
        $end = Date::create(2026, 3, 29, 1, 30, 0, 'UTC');

        $calEvent = CalendarEvent::factory()->create([
            'title'             => 'EU DST spring-forward (Europe/Kyiv)',
            'start_date_time'   => $start,
            'end_date_time'     => $end,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);

        $externalId = $this->service->createEvent($calEvent, $this->integration);
        $this->createdEventIds[] = $externalId;

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

    it('correctly handles a half-hour timezone offset (Asia/Kolkata, UTC+5:30)', function (): void {
        // Asia/Kolkata is UTC+5:30 with no DST
        // 19:30 IST = 14:00 UTC
        $start = Date::create(2026, 6, 15, 14, 0, 0, 'UTC');
        $end = Date::create(2026, 6, 15, 15, 0, 0, 'UTC');

        $calEvent = CalendarEvent::factory()->create([
            'title'             => 'Half-hour offset – Asia/Kolkata',
            'start_date_time'   => $start,
            'end_date_time'     => $end,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);

        $externalId = $this->service->createEvent($calEvent, $this->integration);
        $this->createdEventIds[] = $externalId;

        $fetched = $this->service->fetchEvents(
            $this->integration->refresh(),
            $start->copy()->subMinutes(5),
            $end->copy()->addMinutes(5),
        );

        $match = collect($fetched)->firstWhere('externalId', $externalId);

        // UTC times must always match regardless of local representation
        expect($match)->not->toBeNull()
            ->and($match->startUtc->toIso8601String())->toBe($start->toIso8601String())
            ->and($match->endUtc->toIso8601String())->toBe($end->toIso8601String());
    });

    it('correctly handles a cross-midnight UTC event', function (): void {
        $start = Date::create(2026, 6, 15, 23, 0, 0, 'UTC');
        $end = Date::create(2026, 6, 16, 1, 0, 0, 'UTC'); // next day

        $calEvent = CalendarEvent::factory()->create([
            'title'             => 'Cross-midnight event',
            'start_date_time'   => $start,
            'end_date_time'     => $end,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);

        $externalId = $this->service->createEvent($calEvent, $this->integration);
        $this->createdEventIds[] = $externalId;

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
        $outOfRange = Date::create(2026, 10, 5, 20, 0, 0, 'UTC'); // outside window

        $inCalEvent = CalendarEvent::factory()->create([
            'title'             => 'In-range event',
            'start_date_time'   => $inRange,
            'end_date_time'     => $inRange->copy()->addHour(),
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);
        $outCalEvent = CalendarEvent::factory()->create([
            'title'             => 'Out-of-range event',
            'start_date_time'   => $outOfRange,
            'end_date_time'     => $outOfRange->copy()->addHour(),
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);

        $inId = $this->service->createEvent($inCalEvent, $this->integration);
        $outId = $this->service->createEvent($outCalEvent, $this->integration);
        $this->createdEventIds[] = $inId;
        $this->createdEventIds[] = $outId;

        $fetched = $this->service->fetchEvents(
            $this->integration->refresh(),
            $inRange->copy()->subMinutes(5),
            $inRange->copy()->addHour()->addMinutes(5),
        );

        $fetchedIds = collect($fetched)->pluck('externalId')->all();

        expect($fetchedIds)->toContain($inId);
        expect($fetchedIds)->not->toContain($outId);
    });
});
