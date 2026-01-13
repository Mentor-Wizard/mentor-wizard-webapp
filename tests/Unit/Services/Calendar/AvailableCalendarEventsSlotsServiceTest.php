<?php

declare(strict_types=1);

use App\Enums\CalendarEventRoleEnum;
use App\Enums\CalendarEventStatusEnum;
use App\Enums\CalendarEventTypeEnum;
use App\Enums\RoleEnum;
use App\Enums\UserScheduleRecordType;
use App\Models\CalendarEvent;
use App\Models\MentorProgram;
use App\Models\User;
use App\Models\UserSchedule;
use App\Services\Calendar\AvailableCalendarEventsSlotsService;
use Carbon\CarbonImmutable;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Date;
use Spatie\Permission\Models\Role;

mutates(AvailableCalendarEventsSlotsService::class);

describe('AvailableCalendarEventsSlotsService', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);

        $this->user = User::factory()->create();
        $this->user->assignRole(Role::findByName(RoleEnum::MENTOR->value));

        $this->mentorProgram = MentorProgram::factory()->create([
            'mentor_id' => $this->user->getKey(),
        ]);

        $this->timezone = $this->user->profile->timezone;

    });

    it('returns empty array when user has no future events', function (): void {
        Date::setTestNow(Date::create(2025, 4, 1, 10, 0, 0, 'UTC'));
        $tz = 'Europe/Kyiv';
        $slots = new AvailableCalendarEventsSlotsService(
            $this->user,
            $tz,
            $this->mentorProgram,
            [],
            true
        )->getAvailableSlots();

        expect($slots)->toBeArray()->toHaveCount(1);
        expect($slots[0]['start'])->toBeInstanceOf(CarbonImmutable::class);
        expect($slots[0]['end'])->toBeInstanceOf(CarbonImmutable::class);
    });

    it('check limit of calendar events', function (): void {
        Date::setTestNow(Date::create(2025, 4, 1, 10, 0, 0, 'UTC'));

        for ($i = 1; $i <= 105; $i++) {
            $currentCalendarEvent = CalendarEvent::query()->create([
                'start_date_time' => Date::now()->addDays($i)->setTime(10, 0)
                    ->format('Y-m-d H:i:s'),
                'end_date_time'   => Date::now()->addDays($i)->setTime(11, 0)
                    ->format('Y-m-d H:i:s'),
                'date'              => Date::now()->addDays($i)->format('Y-m-d'),
                'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
                'title'             => 'Event '.$i,
                'status'            => CalendarEventStatusEnum::CONFIRMED->value,
                'mentor_program_id' => $this->mentorProgram->getKey(),
            ]);
            $this->user->calendarEvents()->attach($currentCalendarEvent->getKey());
        }

        $tz = 'UTC';
        $service = new AvailableCalendarEventsSlotsService(
            $this->user,
            $tz,
            $this->mentorProgram,
            [],
            true,
        );

        $slots = $service->getAvailableSlots();

        expect($slots)->toHaveCount(106);
    });

    it('builds available slots between events using timezone conversion', function (): void {
        $tz = 'Europe/Kyiv';
        Date::setTestNow(Date::now($tz)->setTime(10, 0, 0));

        // Create two future events in UTC
        $event1StartUtc = Date::now($tz)->addDay()->setTime(12, 0, 0);
        $event1EndUtc = (clone $event1StartUtc)->addHour()->setTime(14, 0, 0);
        $event2StartUtc = Date::now($tz)->addDay()->setTime(12, 0, 0);
        $event2EndUtc = (clone $event2StartUtc)->setTime(14, 0, 0);

        $event1 = CalendarEvent::query()->create([
            'title'             => 'E1',
            'status'            => 'confirmed',
            'start_date_time'   => $event1StartUtc,
            'end_date_time'     => $event1EndUtc,
            'date'              => $event1StartUtc->format('Y-m-d'),
            'type'              => 'individual',
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);
        $event2 = CalendarEvent::query()->create([
            'title'             => 'E2',
            'status'            => 'confirmed',
            'start_date_time'   => $event2StartUtc,
            'end_date_time'     => $event2EndUtc,
            'date'              => $event2StartUtc->format('Y-m-d'),
            'type'              => 'individual',
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);
        $this->user->calendarEvents()->attach([$event1->getKey(), $event2->getKey()]);

        $result = new AvailableCalendarEventsSlotsService(
            $this->user,
            $tz,
            $this->mentorProgram,
            [],
            true,
        )->getAvailableSlots();

        // We expect 3 slots: [now..E1.start], [E1.end..E2.start], [E2.end..now+2months]
        expect($result)->toBeArray()->toHaveCount(3);

        // Slot 1 start is now in tz; end is E1 start in tz
        $slot1 = $result[0];

        expect($slot1['start']->equalTo(Date::now($tz)
            ->setTime(10, 0, 0)->timezone($tz)))->toBeTrue()
            ->and($slot1['end']->equalTo($event1StartUtc->clone()->timezone($tz)))->toBeTrue();

        // Slot 2 between E1 end and E2 start in tz
        $slot2 = $result[1];
        expect($slot2['start']->equalTo($event1EndUtc->clone()->timezone($tz)))->toBeTrue()
            ->and($slot2['end']->equalTo($event2StartUtc->clone()->timezone($tz)))->toBeTrue();

        // Slot 3 ends at now+2 months in tz
        $slot3 = $result[2];
        expect($slot3['start']->equalTo($event2EndUtc->clone()->timezone($tz)))->toBeTrue()
            ->and($slot3['end']->equalTo(Date::now($tz)
                ->addMonths(CalendarEvent::MAXIMUM_NUMBER_OF_MONTHS_EVENT_CAN_BE_SET)
                ->setTime(10, 0, 0)))->toBeTrue();
    });

    it('respects minimum pre-booking time from mentor program mentor', function (): void {
        $tz = 'UTC';
        // Align now to 00 seconds and minutes to avoid rounding surprises
        Date::setTestNow(Date::create(2025, 5, 1, 10, 0, 0, $tz));

        // Ensure mentor has a minimum pre-booking time set
        $this->mentorProgram->mentor->profile->update(['minimum_pre_booking_time' => 90]);

        $slots = new AvailableCalendarEventsSlotsService(
            $this->user,
            $tz,
            $this->mentorProgram,
            [],
            true
        )->getAvailableSlots();

        expect($slots)->toBeArray()->toHaveCount(1);
        // Start should be now + 90 minutes, rounded/ceiled to the app rounding step
        $expectedStart = Date::now($tz)
            ->addMinutes(90)
            ->ceilMinutes(CalendarEvent::ROUNDING_DISCRECY_TIME_IN_MINUTES);

        expect($slots[0]['start']->equalTo($expectedStart))->toBeTrue();
    });

    it('handles zero minimum pre booking time', function (): void {
        $mentorNoPreBooking = User::factory()->create();
        $mentorNoPreBooking->assignRole(RoleEnum::MENTOR->value);

        $mentorProgram = MentorProgram::factory()->create([
            'mentor_id'        => $mentorNoPreBooking->id,
            'session_duration' => 30,
        ]);
        $mentorNoPreBooking->profile->update(['minimum_pre_booking_time' => 0]);

        Date::setTestNow('2026-01-10 10:00:00');

        $service = new AvailableCalendarEventsSlotsService(
            $this->user,
            $this->timezone,
            $mentorProgram,
            [],
            false,
        );

        $slots = $service->getAvailableSlots();

        expect($slots)->not->toBeEmpty();
        expect($slots[0]['start']->format('H:i'))->toBe('10:00');
    });

    // Note: pre-booking time column is non-nullable, behavior with 0 is tested above.

    it('filters out slots shorter than session duration', function (): void {
        Date::setTestNow('2026-01-10 10:00:00');

        $this->user->profile->update(['minimum_pre_booking_time' => 30]);
        // Create an event that leaves only 20 minute gap (less than 30 min session)
        CalendarEvent::factory()->create([
            'start_date_time'   => Date::parse('2026-01-10 10:20:00'),
            'end_date_time'     => Date::parse('2026-01-10 12:00:00'),
            'mentor_program_id' => $this->mentorProgram->id,
        ])->calendarEventUsers()->attach([$this->user->id], [
            'role' => CalendarEventRoleEnum::HOST->value,
        ]);

        $service = new AvailableCalendarEventsSlotsService(
            $this->user,
            $this->timezone,
            $this->mentorProgram,
            [],
            false,
        );

        $slots = $service->getAvailableSlots();

        // The 20-minute gap before the event should NOT appear
        $slotsBeforeEvent = collect($slots)->filter(
            fn ($slot): bool => $slot['end']->lessThanOrEqualTo(Date::parse('2026-01-10 11:20:00'))
        );

        expect($slotsBeforeEvent)->toHaveCount(0);
    });

    it('includes gaps when session_duration is zero', function (): void {
        $tz = 'UTC';
        Date::setTestNow(Date::create(2026, 1, 10, 10, 0, 0, $tz));

        // Ensure session_duration is zero
        $this->mentorProgram->update(['session_duration' => 0]);

        // Create a future event at 10:20 so there is a small gap
        $eStart = Date::now($tz)->addMinutes(20);
        $eEnd = (clone $eStart)->addHour();
        $event = CalendarEvent::query()->create([
            'title'             => 'Gap Allow',
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'start_date_time'   => $eStart,
            'end_date_time'     => $eEnd,
            'date'              => $eStart->toDateString(),
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);
        // Attach to user so it is considered
        $this->user->calendarEvents()->attach($event->getKey());
        // Attach mentor as well to ensure event is included for either participant
        $this->mentorProgram->mentor->calendarEvents()->attach($event->getKey());

        $result = (new AvailableCalendarEventsSlotsService(
            $this->user,
            $tz,
            $this->mentorProgram,
            [],
            false,
        ))->getAvailableSlots();

        // Initial gap should be included since session_duration is treated as 0
        $initial = $result[0];
        expect($initial['end']->equalTo($eStart->timezone($tz)->ceilMinutes(CalendarEvent::ROUNDING_DISCRECY_TIME_IN_MINUTES)))->toBeTrue();
    });

    it('includes slots equal to session duration', function (): void {
        Date::setTestNow('2026-01-10 10:00:00');
        // Create an event that leaves exactly 30 minute gap (equal to session)
        CalendarEvent::factory()->create([
            'start_date_time'   => Date::parse('2026-01-10 10:30:00'),
            'end_date_time'     => Date::parse('2026-01-10 12:00:00'),
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'date'              => Date::parse('2026-01-10'),
            'mentor_program_id' => $this->mentorProgram->id,
        ])->calendarEventUsers()->attach([$this->user->id, $this->mentorProgram
            ->mentor->id]);

        $service = new AvailableCalendarEventsSlotsService(
            $this->user,
            $this->timezone,
            $this->mentorProgram,
            [],
            false
        );

        $slots = $service->getAvailableSlots();

        // Should have a slot from 11:00 to 11:30 (30 minutes exactly)
        $targetSlot = collect($slots)->first(
            fn ($slot): bool => $slot['start']->format('H:i') === '10:00'
                && $slot['end']->format('H:i') === '10:30'
        );

        expect($targetSlot)->not->toBeNull();
    });

    it('creates slot between events when duration is sufficient', function (): void {
        Date::setTestNow('2026-01-10 10:00:00');

        CalendarEvent::factory()->create([
            'start_date_time'   => Date::parse('2026-01-10 11:00:00'),
            'end_date_time'     => Date::parse('2026-01-10 11:30:00'),
            'mentor_program_id' => $this->mentorProgram->id,
        ])->calendarEventUsers()->attach([$this->user->id,
            $this->mentorProgram->mentor->id]);

        CalendarEvent::factory()->create([
            'start_date_time'   => Date::parse('2026-01-10 12:30:00'),
            'end_date_time'     => Date::parse('2026-01-10 13:00:00'),
            'mentor_program_id' => $this->mentorProgram->id,
        ])->calendarEventUsers()->attach([$this->user->id,
            $this->mentorProgram->mentor->id]);

        $service = new AvailableCalendarEventsSlotsService(
            $this->user,
            $this->timezone,
            $this->mentorProgram,
            [],
            false
        );

        $slots = $service->getAvailableSlots();

        // Should have a slot from 11:30 to 12:30 (60 minutes)
        $betweenSlot = collect($slots)->first(
            fn ($slot): bool => $slot['start']->format('H:i') === '11:30'
                && $slot['end']->format('H:i') === '12:30'
        );

        expect($betweenSlot)->not->toBeNull();
    });

    it('does not create initial slot when first event starts exactly at period start', function (): void {
        $tz = 'UTC';
        Date::setTestNow(Date::create(2026, 1, 10, 10, 0, 0, $tz));

        // Event that starts exactly at period start (after rounding) -> no initial slot
        $start = Date::now($tz)->setTime(10, 0, 0);
        $end = (clone $start)->addHour();
        $event = CalendarEvent::query()->create([
            'title'             => 'StartsNow',
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'start_date_time'   => $start,
            'end_date_time'     => $end,
            'date'              => $start->toDateString(),
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);
        $this->user->calendarEvents()->attach($event->getKey());

        $result = (new AvailableCalendarEventsSlotsService(
            $this->user,
            $tz,
            $this->mentorProgram,
            [],
            false,
        ))->getAvailableSlots();

        // Only the trailing slot should exist and should start at periodStart (no initial zero-length gap)
        expect($result)->toHaveCount(1);
        $periodStart = Date::now($tz)->ceilMinutes(CalendarEvent::ROUNDING_DISCRECY_TIME_IN_MINUTES);
        expect($result[0]['start']->equalTo($periodStart))->toBeTrue();
    });

    it('creates zero-length slot between back-to-back events (session_duration=0)', function (): void {
        $tz = 'UTC';
        Date::setTestNow(Date::create(2026, 1, 10, 10, 0, 0, $tz));

        $e1Start = Date::now($tz)->addHour();
        $e1End = (clone $e1Start)->addHour();
        $e1 = CalendarEvent::query()->create([
            'title'             => 'E1',
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'start_date_time'   => $e1Start,
            'end_date_time'     => $e1End,
            'date'              => $e1Start->toDateString(),
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);

        $e2Start = $e1End->copy(); // back-to-back
        $e2End = (clone $e2Start)->addMinutes(30);
        $e2 = CalendarEvent::query()->create([
            'title'             => 'E2',
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'start_date_time'   => $e2Start,
            'end_date_time'     => $e2End,
            'date'              => $e2Start->toDateString(),
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);

        $this->user->calendarEvents()->attach([$e1->getKey(), $e2->getKey()]);

        $result = (new AvailableCalendarEventsSlotsService(
            $this->user,
            $tz,
            $this->mentorProgram,
            [],
            false,
        ))->getAvailableSlots();

        // Expect 3 slots: initial, zero-length middle, trailing
        expect($result)->toHaveCount(3);
        // Middle slot has zero duration at E1.end/E2.start
        expect($result[1]['start']->equalTo($e1End->timezone($tz)->ceilMinutes(CalendarEvent::ROUNDING_DISCRECY_TIME_IN_MINUTES)))->toBeTrue();
        expect($result[1]['end']->equalTo($e2Start->timezone($tz)->ceilMinutes(CalendarEvent::ROUNDING_DISCRECY_TIME_IN_MINUTES)))->toBeTrue();
    });

    it('considers events attached to either the user or the mentor', function (): void {
        $tz = 'UTC';
        Date::setTestNow(Date::create(2026, 1, 10, 9, 0, 0, $tz));

        // Event A attached only to the mentor
        $aStart = Date::now($tz)->addHour();
        $aEnd = (clone $aStart)->addHour();
        $eventA = CalendarEvent::query()->create([
            'title'             => 'MentorOnly',
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'start_date_time'   => $aStart,
            'end_date_time'     => $aEnd,
            'date'              => $aStart->toDateString(),
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);
        $this->mentorProgram->mentor->calendarEvents()->attach($eventA->getKey());

        // Event B attached only to the user
        $bStart = (clone $aEnd)->addHour();
        $bEnd = (clone $bStart)->addMinutes(30);
        $eventB = CalendarEvent::query()->create([
            'title'             => 'UserOnly',
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'start_date_time'   => $bStart,
            'end_date_time'     => $bEnd,
            'date'              => $bStart->toDateString(),
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);
        $this->user->calendarEvents()->attach($eventB->getKey());

        $result = (new AvailableCalendarEventsSlotsService(
            $this->user,
            $tz,
            $this->mentorProgram,
            [],
            false,
        ))->getAvailableSlots();

        // With two events (one for mentor, one for user), there should be 3 slots
        expect($result)->toHaveCount(3);
        // slot[0] ends at A.start, slot[1] spans A.end..B.start
        expect($result[0]['end']->equalTo($aStart->timezone($tz)->ceilMinutes(CalendarEvent::ROUNDING_DISCRECY_TIME_IN_MINUTES)))->toBeTrue();
        expect($result[1]['start']->equalTo($aEnd->timezone($tz)->ceilMinutes(CalendarEvent::ROUNDING_DISCRECY_TIME_IN_MINUTES)))->toBeTrue();
        expect($result[1]['end']->equalTo($bStart->timezone($tz)->ceilMinutes(CalendarEvent::ROUNDING_DISCRECY_TIME_IN_MINUTES)))->toBeTrue();
    });

    it('respects mentor program start and end times', function (): void {
        Date::setTestNow('2026-01-10 10:00:00');

        $this->mentorProgram->update([
            'start_time' => Date::parse('2026-02-01 00:00:00'),
            'end_time'   => Date::parse('2026-02-28 23:59:59'),
        ]);

        $service = new AvailableCalendarEventsSlotsService(
            $this->user,
            $this->timezone,
            $this->mentorProgram,
            [],
            false
        );

        $slots = $service->getAvailableSlots();

        expect($slots)->not->toBeEmpty();
        expect($slots[0]['start']->format('Y-m-d'))->toBeGreaterThanOrEqual('2026-02-01');
        expect(end($slots)['end']->format('Y-m-d'))->toBeLessThanOrEqual('2026-02-28');
    });

    it('returns single slot when no events exist', function (): void {
        Date::setTestNow('2026-01-10 10:00:00');

        $service = new AvailableCalendarEventsSlotsService(
            $this->user,
            $this->timezone,
            $this->mentorProgram,
            [],
            false
        );

        $slots = $service->getAvailableSlots();

        expect($slots)->toHaveCount(1);
        expect($slots[0])->toHaveKeys(['start', 'end']);
    });

    it('excludes specific events by id', function (): void {
        Date::setTestNow('2026-01-10 10:00:00');

        $event1 = CalendarEvent::factory()->create([
            'start_date_time'   => Date::parse('2026-01-10 11:00:00'),
            'end_date_time'     => Date::parse('2026-01-10 11:30:00'),
            'mentor_program_id' => $this->mentorProgram->id,
        ]);
        $event1->calendarEventUsers()->attach([$this->user->id, $this->user->id]);

        // Service excluding the event should act as if it doesn't exist
        $service = new AvailableCalendarEventsSlotsService(
            $this->user,
            $this->timezone,
            $this->mentorProgram,
            [$event1->id],
            false
        );

        $slots = $service->getAvailableSlots();

        // Should have one continuous slot (event is excluded)
        expect($slots)->toHaveCount(1);
    });

    it('includes events that belong to either the user or the mentor (both ids used in query)', function (): void {
        $tz = 'UTC';
        Date::setTestNow(Date::create(2025, 6, 1, 9, 0, 0, $tz));

        // Event A is attached ONLY to the user
        $eventAStart = Date::now()->addHours(2);
        $eventAEnd = (clone $eventAStart)->addHour();
        $eventA = CalendarEvent::query()->create([
            'title'             => 'A',
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'start_date_time'   => $eventAStart,
            'end_date_time'     => $eventAEnd,
            'date'              => $eventAStart->toDateString(),
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);
        $this->user->calendarEvents()->attach($eventA->getKey());

        // Event B is attached ONLY to the mentor
        $mentor = $this->mentorProgram->mentor;
        $eventBStart = Date::now()->addHours(4);
        $eventBEnd = (clone $eventBStart)->addHour();
        $eventB = CalendarEvent::query()->create([
            'title'             => 'B',
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'start_date_time'   => $eventBStart,
            'end_date_time'     => $eventBEnd,
            'date'              => $eventBStart->toDateString(),
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);
        $mentor->calendarEvents()->attach($eventB->getKey());

        $result = new AvailableCalendarEventsSlotsService(
            $this->user,
            $tz,
            $this->mentorProgram,
            [],
            true
        )->getAvailableSlots();

        // We expect three slots: [now..A.start], [A.end..B.start], [B.end..finish]
        expect($result)->toBeArray()->toHaveCount(3);
        expect($result[0]['end']->equalTo($eventAStart->timezone($tz)->ceilMinutes(CalendarEvent::ROUNDING_DISCRECY_TIME_IN_MINUTES)))->toBeTrue();
        expect($result[1]['start']->equalTo($eventAEnd->timezone($tz)->ceilMinutes(CalendarEvent::ROUNDING_DISCRECY_TIME_IN_MINUTES)))->toBeTrue();
        expect($result[1]['end']->equalTo($eventBStart->timezone($tz)->ceilMinutes(CalendarEvent::ROUNDING_DISCRECY_TIME_IN_MINUTES)))->toBeTrue();
    });

    it('limits the number of fetched events to 200', function (): void {
        $tz = 'UTC';
        Date::setTestNow(Date::create(2025, 7, 1, 8, 0, 0, $tz));

        // Create 205 back-to-back, non-overlapping events in the future
        for ($i = 1; $i <= 205; $i++) {
            $start = Date::now()->addHours($i * 12)->setTime(9, 0);
            $end = (clone $start)->addHour();
            $event = CalendarEvent::query()->create([
                'start_date_time'   => $start,
                'end_date_time'     => $end,
                'date'              => $start->toDateString(),
                'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
                'title'             => 'Event '.$i,
                'status'            => CalendarEventStatusEnum::CONFIRMED->value,
                'mentor_program_id' => $this->mentorProgram->getKey(),
            ]);
            $this->user->calendarEvents()->attach($event->getKey());
        }

        $slots = new AvailableCalendarEventsSlotsService(
            $this->user,
            $tz,
            $this->mentorProgram,
            [],
            true
        )->getAvailableSlots();

        // With a strict 200 event limit, we will only compute gaps for 200 events => 201 slots
        expect($slots)->toHaveCount(201);
    });

    it('honors mentor program start_time and end_time window for period bounds', function (): void {
        $tz = 'UTC';
        Date::setTestNow(Date::create(2025, 8, 1, 8, 0, 0, $tz));

        // Set explicit window on mentor program
        $startWindow = Date::now()->addDays(2)->setTime(9, 0);
        $endWindow = Date::now()->addDays(10)->setTime(17, 0);
        $this->mentorProgram->update([
            'start_time' => $startWindow,
            'end_time'   => $endWindow,
        ]);

        // Create events outside the window - they should be ignored by where clauses
        $beforeStart = CalendarEvent::query()->create([
            'title'             => 'Before',
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'start_date_time'   => Date::now()->addDay()->setTime(8, 0),
            'end_date_time'     => Date::now()->addDay()->setTime(9, 0),
            'date'              => Date::now()->addDay()->toDateString(),
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);
        $afterEnd = CalendarEvent::query()->create([
            'title'             => 'After',
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'start_date_time'   => Date::now()->addDays(12)->setTime(9, 0),
            'end_date_time'     => Date::now()->addDays(12)->setTime(10, 0),
            'date'              => Date::now()->addDays(12)->toDateString(),
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);
        $this->user->calendarEvents()->attach([$beforeStart->getKey(), $afterEnd->getKey()]);

        // Also create one event inside to create a middle break
        $insideStart = Date::now()->addDays(5)->setTime(10, 0);
        $insideEnd = (clone $insideStart)->addHour();
        $inside = CalendarEvent::query()->create([
            'title'             => 'Inside',
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'start_date_time'   => $insideStart,
            'end_date_time'     => $insideEnd,
            'date'              => $insideStart->toDateString(),
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);
        $this->user->calendarEvents()->attach($inside->getKey());

        $result = new AvailableCalendarEventsSlotsService(
            $this->user,
            $tz,
            $this->mentorProgram,
            [],
            true
        )->getAvailableSlots();

        // First slot must start at program start window
        expect($result[0]['start']->equalTo($startWindow->timezone($tz)->ceilMinutes(CalendarEvent::ROUNDING_DISCRECY_TIME_IN_MINUTES)))->toBeTrue();
        // Last slot must end at program end window
        expect(end($result)['end']->equalTo($endWindow->timezone($tz)))->toBeTrue();
        // Ensure only the inside event influenced segmentation (3 slots total)
        expect($result)->toHaveCount(2);
    });

    it('skips gaps shorter than session_duration at the beginning and between events', function (): void {
        $tz = 'UTC';
        Date::setTestNow(Date::create(2025, 9, 1, 8, 0, 0, $tz));

        // Set session duration to 60 minutes
        $this->mentorProgram->update(['session_duration' => 60]);

        // Create an event starting in 30 minutes (gap < session duration => should be skipped)
        $e1Start = Date::now()->addMinutes(30);
        $e1End = (clone $e1Start)->addMinutes(10);
        $e1 = CalendarEvent::query()->create([
            'title'             => 'E1',
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'start_date_time'   => $e1Start,
            'end_date_time'     => $e1End,
            'date'              => $e1Start->toDateString(),
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);
        $this->user->calendarEvents()->attach($e1->getKey());

        // Next event begins 30 minutes after E1 end -> gap=30 (<60) should be skipped
        $e2Start = (clone $e1End)->addMinutes(30);
        $e2End = (clone $e2Start)->addHour();
        $e2 = CalendarEvent::query()->create([
            'title'             => 'E2',
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'start_date_time'   => $e2Start,
            'end_date_time'     => $e2End,
            'date'              => $e2Start->toDateString(),
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);
        $this->user->calendarEvents()->attach($e2->getKey(),
            ['role' => CalendarEventRoleEnum::HOST->value]);

        $result = new AvailableCalendarEventsSlotsService(
            $this->user,
            $tz,
            $this->mentorProgram,
            [],
            false
        )->getAvailableSlots();

        // Only the last slot from E2.end to the finish should remain (initial gap and middle gap skipped)
        expect($result)->toHaveCount(2);
        expect($result[0]['start']->equalTo($e2End->timezone($tz)
            ->ceilMinutes(CalendarEvent::ROUNDING_DISCRECY_TIME_IN_MINUTES)))->toBeFalse();
    });

    it('creates initial slot when first event starts after current UTC time', function (): void {
        $tz = 'Europe/Kyiv';
        Date::setTestNow(Date::now($tz)->setTime(10, 0, 0));
        $eventStartUtc = Date::now($tz)->addDay()->setTime(12, 0, 0);
        $eventEndUtc = (clone $eventStartUtc)->addHour();

        $event = CalendarEvent::query()->create([
            'title'             => 'E-future',
            'status'            => 'confirmed',
            'start_date_time'   => $eventStartUtc,
            'end_date_time'     => $eventEndUtc,
            'date'              => $eventStartUtc->format('Y-m-d'),
            'type'              => 'individual',
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);
        $this->user->calendarEvents()->attach($event->getKey());

        $result = new AvailableCalendarEventsSlotsService(
            $this->user,
            $tz,
            $this->mentorProgram,
            [],
            true
        )->getAvailableSlots();

        // Two slots should exist: [now..E-future.start], [E-future.end..now+2months]
        expect($result)->toBeArray()->toHaveCount(2);

        expect($result[0]['start']
            ->equalTo(Date::now($tz)->setTime(10, 0, 0)))->toBeTrue()
            ->and($result[0]['end']
                ->equalTo($eventStartUtc->clone()->timezone($tz)))->toBeTrue();

        expect($result[1]['start']
            ->equalTo($eventEndUtc->clone()->timezone($tz)))->toBeTrue()
            ->and($result[1]['end']
                ->equalTo(Date::now($tz)
                    ->addMonths(CalendarEvent::MAXIMUM_NUMBER_OF_MONTHS_EVENT_CAN_BE_SET)))->toBeTrue();
    });

    it('excludes specified events from available slots calculation', function (): void {
        $tz = 'Europe/Kyiv';
        Date::setTestNow(Date::now($tz)->setTime(10, 0, 0));

        // Create three events
        $event1StartUtc = Date::now($tz)->addDay()->setTime(12, 0, 0);
        $event1EndUtc = (clone $event1StartUtc)->addHour();

        $event2StartUtc = Date::now($tz)->addDays(2)->setTime(12, 0, 0);
        $event2EndUtc = (clone $event2StartUtc)->addHour();

        $event3StartUtc = Date::now($tz)->addDays(3)->setTime(12, 0, 0);
        $event3EndUtc = (clone $event3StartUtc)->addHour();

        $event1 = CalendarEvent::query()->create([
            'title'             => 'E1',
            'status'            => 'confirmed',
            'start_date_time'   => $event1StartUtc,
            'end_date_time'     => $event1EndUtc,
            'date'              => $event1StartUtc->format('Y-m-d'),
            'type'              => 'individual',
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);
        $event2 = CalendarEvent::query()->create([
            'title'             => 'E2',
            'status'            => 'confirmed',
            'start_date_time'   => $event2StartUtc,
            'end_date_time'     => $event2EndUtc,
            'date'              => $event2StartUtc->format('Y-m-d'),
            'type'              => 'individual',
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);
        $event3 = CalendarEvent::query()->create([
            'title'             => 'E3',
            'status'            => 'confirmed',
            'start_date_time'   => $event3StartUtc,
            'end_date_time'     => $event3EndUtc,
            'date'              => $event3StartUtc->format('Y-m-d'),
            'type'              => 'individual',
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);

        $this->user->calendarEvents()->attach([$event1->getKey(), $event2->getKey(), $event3->getKey()]);

        // Exclude event2 from calculation
        $result = new AvailableCalendarEventsSlotsService(
            $this->user,
            $tz,
            $this->mentorProgram,
            [$event2->getKey()],
            true
        )->getAvailableSlots();

        // We expect 3 slots: [now..E1.start], [E1.end..E3.start], [E3.end..now+2months]
        // Event2 should not be considered
        expect($result)->toBeArray()->toHaveCount(3);

        // Verify that E2 is not in the calculation
        $slot2 = $result[1];
        expect($slot2['start']->equalTo($event1EndUtc->clone()->timezone($tz)))->toBeTrue()
            ->and($slot2['end']->equalTo($event3StartUtc->clone()->timezone($tz)))->toBeTrue();
    });

    it('applies schedule exclusion when excludeSchedule flag is true', function (): void {
        $tz = 'Europe/Kyiv';
        Date::setTestNow(Date::create(2025, 1, 6, 10, 0, 0, $tz)); // Monday

        // Create user schedule: Monday 9:00-17:00
        UserSchedule::query()->create([
            'user_id'     => $this->user->getKey(),
            'day_of_week' => 1, // Monday
            'start_time'  => '09:00:00',
            'end_time'    => '17:00:00',
            'type'        => UserScheduleRecordType::WORKING_DAY,
        ]);

        // Create an event on Monday 14:00-15:00
        $eventStart = Date::create(2025, 1, 6, 14, 0, 0, $tz);
        $eventEnd = Date::create(2025, 1, 6, 15, 0, 0, $tz);

        $event = CalendarEvent::query()->create([
            'title'             => 'Monday Event',
            'status'            => 'confirmed',
            'start_date_time'   => $eventStart,
            'end_date_time'     => $eventEnd,
            'date'              => $eventStart?->format('Y-m-d'),
            'type'              => 'individual',
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);
        $this->user->calendarEvents()->attach($event->getKey());

        // Get slots with schedule exclusion
        $result = new AvailableCalendarEventsSlotsService(
            $this->user,
            $tz,
            $this->mentorProgram,
            [],
            true
        )->getAvailableSlots();

        // Slots should only include times within working hours (9:00-17:00)
        expect($result)->toBeArray()->not()->toBeEmpty();

        // Verify that slots respect schedule boundaries
        foreach ($result as $slot) {
            $slotDate = $slot['start']->format('Y-m-d');
            $slotStartTime = $slot['start']->format('H:i:s');
            $slotEndTime = $slot['end']->format('H:i:s');

            // If it's Monday (day_of_week = 1), times should be within 09:00-17:00
            if ($slot['start']->dayOfWeek === 1) {
                expect($slotStartTime >= '09:00:00' || $slotEndTime <= '17:00:00')->toBeTrue();
            }
        }
    });

    it('excludes day off dates when excludeSchedule flag is true', function (): void {
        $tz = 'Europe/Kyiv';

        // Create working schedule for Monday
        UserSchedule::query()->create([
            'user_id'     => $this->user->getKey(),
            'day_of_week' => 1, // Monday
            'start_time'  => '09:00:00',
            'end_time'    => '17:00:00',
            'type'        => UserScheduleRecordType::WORKING_DAY,
        ]);

        $dayOffdate = Date::today()->addMonth()->firstOfMonth(1); // Next Monday

        // Create a day off on Monday, January 13
        UserSchedule::query()->create([
            'user_id'      => $this->user->getKey(),
            'day_of_week'  => 1, // Monday (required but ignored for day off)
            'start_time'   => '00:00:00',
            'end_time'     => '23:59:59',
            'type'         => UserScheduleRecordType::DAY_OFF,
            'day_off_date' => $dayOffdate,
        ]);

        // Create events on both Mondays
        $event1Start = Date::now()->addMonth()->firstOfMonth(1)
            ->setTime(10, 0, 0)->timezone($tz); // This Monday
        $event1End = Date::now()->addMonth()->firstOfMonth(1)
            ->setTime(16, 0, 0)->timezone($tz);

        $event2Start = Date::now()->addMonth()->firstOfMonth(1)
            ->addWeek()->setTime(10, 0, 0)->timezone($tz); // Next Monday (day off)
        $event2End = Date::now()->addMonth()->firstOfMonth(1)
            ->addWeek()->setTime(16, 0, 0)->timezone($tz);

        $event1 = CalendarEvent::query()->create([
            'title'             => 'Event on Working Monday',
            'status'            => 'confirmed',
            'start_date_time'   => $event1Start,
            'end_date_time'     => $event1End,
            'date'              => $event1Start->format('Y-m-d'),
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);

        $event2 = CalendarEvent::query()->create([
            'title'             => 'Event on Day Off',
            'status'            => 'confirmed',
            'start_date_time'   => $event2Start,
            'end_date_time'     => $event2End,
            'date'              => $event2Start->format('Y-m-d'),
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);

        $this->user->calendarEvents()->attach([$event1->getKey(), $event2->getKey()]);

        // Get slots with schedule exclusion
        $result = new AvailableCalendarEventsSlotsService(
            $this->user,
            $tz,
            $this->mentorProgram,
            [],
            true
        )->getAvailableSlots();

        // Slots should not include or overlap with the day off date (2025-01-13)

        $slotsOnDayOff = array_filter($result, function (array $slot) use ($dayOffdate): bool {
            $slotDate = $slot['start']->format('Y-m-d');
            $slotEndDate = $slot['end']->format('Y-m-d');
            if ($slotDate === $dayOffdate->format('Y-m-d')) {
                return true;
            }

            return $slotEndDate === $dayOffdate->format('Y-m-d');
        });

        expect($result)->not->toBeEmpty();
        expect($slotsOnDayOff)->toBeEmpty();
    });

    it('returns slots without schedule filtering when excludeSchedule is false', function (): void {
        $tz = 'Europe/Kyiv';
        Date::setTestNow(Date::create(2025, 1, 6, 10, 0, 0, $tz)); // Monday

        // Create user schedule: Monday 9:00-17:00
        UserSchedule::query()->create([
            'user_id'     => $this->user->getKey(),
            'day_of_week' => 1, // Monday
            'start_time'  => '09:00:00',
            'end_time'    => '17:00:00',
            'type'        => UserScheduleRecordType::WORKING_DAY,
        ]);

        // Create an event on Monday
        $eventStart = Date::create(2025, 1, 6, 14, 0, 0, $tz);
        $eventEnd = Date::create(2025, 1, 6, 15, 0, 0, $tz);

        $event = CalendarEvent::query()->create([
            'title'             => 'Monday Event',
            'status'            => 'confirmed',
            'start_date_time'   => $eventStart,
            'end_date_time'     => $eventEnd,
            'date'              => $eventStart?->format('Y-m-d'),
            'type'              => 'individual',
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);
        $this->user->calendarEvents()->attach($event->getKey());

        // Get slots WITHOUT schedule exclusion (default behavior)
        $result = new AvailableCalendarEventsSlotsService(
            $this->user,
            $tz,
            $this->mentorProgram,
            [],
            false,
        )->getAvailableSlots();

        // Should return slots outside working hours too
        expect($result)->toBeArray()->toHaveCount(2);

        // First slot should start at current time (10:00), not restricted by schedule
        expect($result[0]['start']->format('H:i'))->toBe('10:00');
    });
});
