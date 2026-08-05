<?php

declare(strict_types=1);

use App\DTO\ExternalCalendar\ExternalCalendarEventData;
use App\Enums\CalendarProviderEnum;
use App\Enums\CalendarSyncStatusEnum;
use App\Enums\MentorSessionTypeEnum;
use App\Models\MentorProgram;
use App\Models\User;
use App\Models\UserCalendarIntegration;
use App\Services\ExternalCalendar\OutlookExternalCalendarService;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Date;
use Modules\Calendar\Enums\CalendarEventTypeEnum;
use Modules\Calendar\Models\CalendarEvent;

/**
 * Microsoft Outlook / Graph API integration tests.
 *
 * Required env variables:
 *
 *   TEST_OUTLOOK_ACCESS_TOKEN    – valid Microsoft Graph access token
 *   TEST_OUTLOOK_REFRESH_TOKEN   – refresh token (optional, used to renew the access token)
 *   TEST_OUTLOOK_CALENDAR_ID     – Outlook calendar ID (or "me" for the default)
 *
 *   Also requires the application-level credentials in the regular config:
 *   MICROSOFT_CLIENT_ID, MICROSOFT_CLIENT_SECRET
 *
 * Run with:
 *   php artisan test --testsuite=Integration --filter=Outlook
 */
describe('Outlook Calendar Integration', function (): void {
    beforeEach(function (): void {
        $this->createdEventIds = [];

        if (! config('calendar.testing.outlook.access_token')) {
            $this->markTestSkipped('Outlook Calendar credentials not configured. Set TEST_OUTLOOK_ACCESS_TOKEN.');
        }

        config(['calendar.encryption_key1' => config('calendar.encryption_key1') ?: base64_encode(random_bytes(32))]);

        $this->seed(RoleSeeder::class);

        $this->user = User::factory()->create();

        $this->mentorProgram = MentorProgram::factory()->create([
            'mentor_id' => $this->user->getKey(),
        ]);

        $this->integration = UserCalendarIntegration::factory()->create([
            'user_id'          => $this->user->getKey(),
            'provider'         => CalendarProviderEnum::OUTLOOK,
            'access_token'     => config('calendar.testing.outlook.access_token'),
            'refresh_token'    => config('calendar.testing.outlook.refresh_token'),
            'client_id'        => null,
            'client_secret'    => null,
            'calendar_id'      => config('calendar.testing.outlook.calendar_id'),
            'sync_status'      => CalendarSyncStatusEnum::ACTIVE,
            'token_expires_at' => now()->subMinute(),
        ]);

        $this->service = resolve(OutlookExternalCalendarService::class);
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
            'title'             => 'Integration Test Event – Outlook UTC roundtrip',
            'description'       => 'Created by automated test',
            'start_date_time'   => $start,
            'end_date_time'     => $end,
            'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION,
            'type'              => CalendarEventTypeEnum::INDIVIDUAL,
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
            ->and($match->title)->toBe('Integration Test Event – Outlook UTC roundtrip')
            ->and($match->startUtc->toIso8601String())->toBe($start->toIso8601String())
            ->and($match->endUtc->toIso8601String())->toBe($end->toIso8601String());
    });

    it('returns ExternalCalendarEventData instances', function (): void {
        $start = Date::create(2026, 8, 10, 10, 0, 0, 'UTC');
        $end = $start->copy()->addHour();

        $calEvent = CalendarEvent::factory()->create([
            'title'             => 'Outlook DTO type check',
            'start_date_time'   => $start,
            'end_date_time'     => $end,
            'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION,
            'type'              => CalendarEventTypeEnum::INDIVIDUAL,
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

        expect($match)->toBeInstanceOf(ExternalCalendarEventData::class);
    });

    // ──────────────────────────────────────────────────────────────────────────
    // Timezone edge cases
    // ──────────────────────────────────────────────────────────────────────────

    it('correctly roundtrips an event at UTC midnight', function (): void {
        $start = Date::create(2026, 9, 1, 0, 0, 0, 'UTC');
        $end = Date::create(2026, 9, 1, 1, 0, 0, 'UTC');

        $calEvent = CalendarEvent::factory()->create([
            'title'             => 'Outlook – midnight UTC event',
            'start_date_time'   => $start,
            'end_date_time'     => $end,
            'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION,
            'type'              => CalendarEventTypeEnum::INDIVIDUAL,
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
        // 01:30 EST = 06:30 UTC
        $start = Date::create(2026, 3, 8, 6, 30, 0, 'UTC');
        $end = Date::create(2026, 3, 8, 7, 0, 0, 'UTC');

        $calEvent = CalendarEvent::factory()->create([
            'title'             => 'Outlook – US DST spring-forward before',
            'start_date_time'   => $start,
            'end_date_time'     => $end,
            'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION,
            'type'              => CalendarEventTypeEnum::INDIVIDUAL,
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
        // 03:30 EDT = 07:30 UTC
        $start = Date::create(2026, 3, 8, 7, 30, 0, 'UTC');
        $end = Date::create(2026, 3, 8, 8, 0, 0, 'UTC');

        $calEvent = CalendarEvent::factory()->create([
            'title'             => 'Outlook – US DST spring-forward after',
            'start_date_time'   => $start,
            'end_date_time'     => $end,
            'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION,
            'type'              => CalendarEventTypeEnum::INDIVIDUAL,
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

    it('correctly roundtrips an event during US fall-back (2026-11-01)', function (): void {
        // 05:00–06:00 UTC = 01:00–02:00 EDT (before the clocks fall back at 06:00 UTC)
        $start = Date::create(2026, 11, 1, 5, 0, 0, 'UTC');
        $end = Date::create(2026, 11, 1, 6, 0, 0, 'UTC');

        $calEvent = CalendarEvent::factory()->create([
            'title'             => 'Outlook – US DST fall-back',
            'start_date_time'   => $start,
            'end_date_time'     => $end,
            'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION,
            'type'              => CalendarEventTypeEnum::INDIVIDUAL,
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

    it('correctly handles a cross-midnight UTC event', function (): void {
        $start = Date::create(2026, 6, 15, 23, 0, 0, 'UTC');
        $end = Date::create(2026, 6, 16, 1, 0, 0, 'UTC');

        $calEvent = CalendarEvent::factory()->create([
            'title'             => 'Outlook – cross-midnight event',
            'start_date_time'   => $start,
            'end_date_time'     => $end,
            'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION,
            'type'              => CalendarEventTypeEnum::INDIVIDUAL,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);

        $externalId = $this->service->createEvent($calEvent, $this->integration);
        $this->createdEventIds[] = $externalId;

        // Fetch window must span both days
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
            'title'             => 'Outlook – in-range event',
            'start_date_time'   => $inRange,
            'end_date_time'     => $inRange->copy()->addHour(),
            'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION,
            'type'              => CalendarEventTypeEnum::INDIVIDUAL,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);
        $outCalEvent = CalendarEvent::factory()->create([
            'title'             => 'Outlook – out-of-range event',
            'start_date_time'   => $outOfRange,
            'end_date_time'     => $outOfRange->copy()->addHour(),
            'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION,
            'type'              => CalendarEventTypeEnum::INDIVIDUAL,
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

        expect($fetchedIds)->toContain($inId)->not->toContain($outId);
    });
});
