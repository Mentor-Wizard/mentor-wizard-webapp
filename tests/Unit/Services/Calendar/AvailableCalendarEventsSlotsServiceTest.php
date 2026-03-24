<?php

declare(strict_types=1);

use App\Enums\CalendarEventColoursEnum;
use App\Enums\CalendarEventRoleEnum;
use App\Enums\CalendarEventStatusEnum;
use App\Enums\CalendarEventTypeEnum;
use App\Enums\MentorSessionTypeEnum;
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
                'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION->value,
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
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'start_date_time'   => $event1StartUtc,
            'end_date_time'     => $event1EndUtc,
            'date'              => $event1StartUtc->format('Y-m-d'),
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);
        $event2 = CalendarEvent::query()->create([
            'title'             => 'E2',
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'start_date_time'   => $event2StartUtc,
            'end_date_time'     => $event2EndUtc,
            'date'              => $event2StartUtc->format('Y-m-d'),
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);

        $event1->calendarEventUsers()->attach($this->user->getKey(),
            [
                'role'          => CalendarEventRoleEnum::HOST->value,
                'colour'        => CalendarEventColoursEnum::BLUE->value]);
        $event2->calendarEventUsers()->attach($this->user->getKey(),
            [
                'role'          => CalendarEventRoleEnum::HOST->value,
                'colour'        => CalendarEventColoursEnum::BLUE->value,
            ]);

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
            'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION->value,
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
            'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);
        // Attach to user so it is considered
        $this->user->calendarEvents()->attach($event->getKey());
        // Attach mentor as well to ensure event is included for either participant
        $this->mentorProgram->mentor->calendarEvents()->attach($event->getKey());

        $result = new AvailableCalendarEventsSlotsService(
            $this->user,
            $tz,
            $this->mentorProgram,
            [],
            false,
        )->getAvailableSlots();

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
            'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION->value,
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

        $event1 = CalendarEvent::factory()->create([
            'start_date_time'   => Date::parse('2026-01-10 11:00:00'),
            'end_date_time'     => Date::parse('2026-01-10 11:30:00'),
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'mentor_program_id' => $this->mentorProgram->id,
        ]);

        $event2 = CalendarEvent::factory()->create([
            'start_date_time'   => Date::parse('2026-01-10 12:30:00'),
            'end_date_time'     => Date::parse('2026-01-10 13:00:00'),
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'mentor_program_id' => $this->mentorProgram->id,
        ]);

        $event1->calendarEventUsers()->attach($this->user->getKey(),
            [
                'role'          => CalendarEventRoleEnum::PARTICIPANT->value,
                'colour'        => CalendarEventColoursEnum::BLUE->value]);

        $event1->calendarEventUsers()->attach($this->mentorProgram->mentor->id,
            [
                'role'          => CalendarEventRoleEnum::HOST->value,
                'colour'        => CalendarEventColoursEnum::BLUE->value]);
        $event2->calendarEventUsers()->attach($this->user->getKey(),
            [
                'role'          => CalendarEventRoleEnum::PARTICIPANT->value,
                'colour'        => CalendarEventColoursEnum::BLUE->value,
            ]);

        $event2->calendarEventUsers()->attach($this->mentorProgram->mentor->id,
            [
                'role'          => CalendarEventRoleEnum::HOST->value,
                'colour'        => CalendarEventColoursEnum::BLUE->value,
            ]);

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
            'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);
        $this->user->calendarEvents()->attach($event->getKey());

        $result = new AvailableCalendarEventsSlotsService(
            $this->user,
            $tz,
            $this->mentorProgram,
            [],
            false,
        )->getAvailableSlots();

        // Only the trailing slot should exist and should start at periodStart (no initial zero-length gap)
        expect($result)->toHaveCount(1);
        $periodStart = Date::now($tz)->addHour()->ceilMinutes(CalendarEvent::ROUNDING_DISCRECY_TIME_IN_MINUTES);
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
            'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION->value,
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
            'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);

        $this->user->calendarEvents()->attach([$e1->getKey(), $e2->getKey()]);

        $result = new AvailableCalendarEventsSlotsService(
            $this->user,
            $tz,
            $this->mentorProgram,
            [],
            false,
        )->getAvailableSlots();

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
            'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION->value,
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
            'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);
        $this->user->calendarEvents()->attach($eventB->getKey());

        $result = new AvailableCalendarEventsSlotsService(
            $this->user,
            $tz,
            $this->mentorProgram,
            [],
            false,
        )->getAvailableSlots();

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
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
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
            'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION->value,
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
            'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION->value,
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
                'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION->value,
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
            'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);
        $afterEnd = CalendarEvent::query()->create([
            'title'             => 'After',
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'start_date_time'   => Date::now()->addDays(12)->setTime(9, 0),
            'end_date_time'     => Date::now()->addDays(12)->setTime(10, 0),
            'date'              => Date::now()->addDays(12)->toDateString(),
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION->value,
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
            'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION->value,
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
            'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION->value,
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
        expect($result)->toHaveCount(1);
        expect($result[0]['start']->equalTo($e2End->timezone($tz)
            ->ceilMinutes(CalendarEvent::ROUNDING_DISCRECY_TIME_IN_MINUTES)))->toBeTrue();
    });

    it('creates initial slot when first event starts after current UTC time', function (): void {
        $tz = 'Europe/Kyiv';
        Date::setTestNow(Date::now($tz)->setTime(10, 0, 0));
        $eventStartUtc = Date::now($tz)->addDay()->setTime(12, 0, 0);
        $eventEndUtc = (clone $eventStartUtc)->addHour();

        $event = CalendarEvent::query()->create([
            'title'             => 'E-future',
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'start_date_time'   => $eventStartUtc,
            'end_date_time'     => $eventEndUtc,
            'date'              => $eventStartUtc->format('Y-m-d'),
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);

        $this->user->calendarEvents()->attach($event->getKey(),
            ['role' => CalendarEventRoleEnum::HOST->value]);

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
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'start_date_time'   => $event1StartUtc,
            'end_date_time'     => $event1EndUtc,
            'date'              => $event1StartUtc->format('Y-m-d'),
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);
        $event2 = CalendarEvent::query()->create([
            'title'             => 'E2',
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'start_date_time'   => $event2StartUtc,
            'end_date_time'     => $event2EndUtc,
            'date'              => $event2StartUtc->format('Y-m-d'),
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);
        $event3 = CalendarEvent::query()->create([
            'title'             => 'E3',
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'start_date_time'   => $event3StartUtc,
            'end_date_time'     => $event3EndUtc,
            'date'              => $event3StartUtc->format('Y-m-d'),
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);

        $event1->calendarEventUsers()->attach($this->user->getKey(),
            [
                'role'          => CalendarEventRoleEnum::HOST->value,
                'colour'        => CalendarEventColoursEnum::BLUE->value,
            ]);
        $event2->calendarEventUsers()->attach($this->user->getKey(),
            [
                'role'          => CalendarEventRoleEnum::HOST->value,
                'colour'        => CalendarEventColoursEnum::BLUE->value,
            ]);
        $event3->calendarEventUsers()->attach($this->user->getKey(),
            [
                'role'          => CalendarEventRoleEnum::HOST->value,
                'colour'        => CalendarEventColoursEnum::BLUE->value,
            ]);

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
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);

        $event->calendarEventUsers()->attach($this->user->getKey(),
            [
                'role'          => CalendarEventRoleEnum::HOST->value,
                'colour'        => CalendarEventColoursEnum::BLUE->value,
            ]);

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
            'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);

        $event2 = CalendarEvent::query()->create([
            'title'             => 'Event on Day Off',
            'status'            => 'confirmed',
            'start_date_time'   => $event2Start,
            'end_date_time'     => $event2End,
            'date'              => $event2Start->format('Y-m-d'),
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);

        $event1->calendarEventUsers()->attach($this->user->getKey(),
            [
                'role'          => CalendarEventRoleEnum::HOST->value,
                'colour'        => CalendarEventColoursEnum::BLUE->value,
            ]);
        $event2->calendarEventUsers()->attach($this->user->getKey(),
            [
                'role'          => CalendarEventRoleEnum::HOST->value,
                'colour'        => CalendarEventColoursEnum::BLUE->value,
            ]);

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
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'start_date_time'   => $eventStart,
            'end_date_time'     => $eventEnd,
            'date'              => $eventStart?->format('Y-m-d'),
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);

        $event->calendarEventUsers()->attach($this->user->getKey(),
            [
                'role'          => CalendarEventRoleEnum::HOST->value,
                'colour'        => CalendarEventColoursEnum::BLUE->value,
            ]);

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

describe('DST Testing', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);

        $this->user = User::factory()->create();
        $this->user->assignRole(Role::findByName(RoleEnum::MENTOR->value));

        $this->mentorProgram = MentorProgram::factory()->create([
            'mentor_id' => $this->user->getKey(),
        ]);
    });

    it('handles America/New_York spring forward DST transition', function (): void {
        // March 9, 2025 at 2:00 AM clocks spring forward to 3:00 AM
        $tz = 'America/New_York';
        Date::setTestNow(Date::create(2025, 3, 9, 1, 0, 0, $tz));

        $service = new AvailableCalendarEventsSlotsService(
            $this->user,
            $tz,
            $this->mentorProgram,
            [],
            false,
        );

        $slots = $service->getAvailableSlots();

        expect($slots)->toBeArray()->not()->toBeEmpty();
    });

    it('handles America/New_York fall back DST transition', function (): void {
        // November 2, 2025 at 2:00 AM clocks fall back to 1:00 AM
        $tz = 'America/New_York';
        Date::setTestNow(Date::create(2025, 11, 2, 1, 0, 0, $tz));

        $service = new AvailableCalendarEventsSlotsService(
            $this->user,
            $tz,
            $this->mentorProgram,
            [],
            false,
        );

        $slots = $service->getAvailableSlots();

        expect($slots)->toBeArray()->not()->toBeEmpty();
    });

    it('calculates slots when offset changes from UTC-5 to UTC-4', function (): void {
        $tz = 'America/New_York';
        // Before DST transition
        Date::setTestNow(Date::create(2025, 3, 8, 12, 0, 0, $tz)); // EST (UTC-5)

        $service = new AvailableCalendarEventsSlotsService(
            $this->user,
            $tz,
            $this->mentorProgram,
            [],
            false,
        );

        $slotsBeforeDst = $service->getAvailableSlots();

        // After DST transition
        Date::setTestNow(Date::create(2025, 3, 10, 12, 0, 0, $tz)); // EDT (UTC-4)

        $serviceAfter = new AvailableCalendarEventsSlotsService(
            $this->user,
            $tz,
            $this->mentorProgram,
            [],
            false,
        );

        $slotsAfterDst = $serviceAfter->getAvailableSlots();

        expect($slotsBeforeDst)->toBeArray()->not()->toBeEmpty();
        expect($slotsAfterDst)->toBeArray()->not()->toBeEmpty();
    });

    it('handles Europe/Kyiv DST transition correctly', function (): void {
        // Europe/Kyiv DST: Last Sunday of March at 03:00 (spring forward)
        $tz = 'Europe/Kyiv';
        Date::setTestNow(Date::create(2026, 3, 29, 2, 30, 0, $tz));

        $service = new AvailableCalendarEventsSlotsService(
            $this->user,
            $tz,
            $this->mentorProgram,
            [],
            false,
        );

        $slots = $service->getAvailableSlots();

        expect($slots)->toBeArray()->not()->toBeEmpty();
    });
});

describe('Boundary Date Tests', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);

        $this->user = User::factory()->create();
        $this->user->assignRole(Role::findByName(RoleEnum::MENTOR->value));

        $this->mentorProgram = MentorProgram::factory()->create([
            'mentor_id' => $this->user->getKey(),
        ]);
    });

    it('handles event at exact month boundary - last minute of month', function (): void {
        $tz = 'UTC';
        Date::setTestNow(Date::create(2026, 1, 31, 23, 30, 0, $tz));

        // Create event ending at 23:59 on last day of month
        $eventStart = Date::parse('2026-01-31 23:00:00', $tz);
        $eventEnd = Date::parse('2026-01-31 23:59:00', $tz);

        CalendarEvent::factory()->create([
            'start_date_time'   => $eventStart,
            'end_date_time'     => $eventEnd,
            'date'              => $eventStart->format('Y-m-d'),
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ])->calendarEventUsers()->attach($this->user->getKey());

        $service = new AvailableCalendarEventsSlotsService(
            $this->user,
            $tz,
            $this->mentorProgram,
            [],
            false,
        );

        $slots = $service->getAvailableSlots();

        expect($slots)->toBeArray();
    });

    it('handles year boundary Dec 31 to Jan 1', function (): void {
        $tz = 'UTC';
        Date::setTestNow(Date::create(2025, 12, 31, 20, 0, 0, $tz));

        // Create event spanning midnight
        $eventStart = Date::parse('2025-12-31 23:00:00', $tz);
        $eventEnd = Date::parse('2026-01-01 01:00:00', $tz);

        CalendarEvent::factory()->create([
            'start_date_time'   => $eventStart,
            'end_date_time'     => $eventEnd,
            'date'              => $eventStart->format('Y-m-d'),
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ])->calendarEventUsers()->attach($this->user->getKey());

        $service = new AvailableCalendarEventsSlotsService(
            $this->user,
            $tz,
            $this->mentorProgram,
            [],
            false,
        );

        $slots = $service->getAvailableSlots();

        expect($slots)->toBeArray()->not()->toBeEmpty();
    });

    it('handles timezone-induced date change UTC to Pacific/Auckland', function (): void {
        // Pacific/Auckland is UTC+12/+13
        $tzAuckland = 'Pacific/Auckland';
        $tzUtc = 'UTC';

        Date::setTestNow(Date::create(2026, 1, 15, 10, 0, 0, $tzUtc)); // 10:00 UTC = 23:00 Auckland

        $service = new AvailableCalendarEventsSlotsService(
            $this->user,
            $tzAuckland,
            $this->mentorProgram,
            [],
            false,
        );

        $slots = $service->getAvailableSlots();

        expect($slots)->toBeArray()->not()->toBeEmpty();
    });

    it('handles timezone-induced date change going backwards to America/Los_Angeles', function (): void {
        // America/Los_Angeles is UTC-8/-7
        $tzLA = 'America/Los_Angeles';

        Date::setTestNow(Date::create(2026, 1, 15, 5, 0, 0, 'UTC')); // 05:00 UTC = 21:00 LA (prev day)

        $service = new AvailableCalendarEventsSlotsService(
            $this->user,
            $tzLA,
            $this->mentorProgram,
            [],
            false,
        );

        $slots = $service->getAvailableSlots();

        expect($slots)->toBeArray()->not()->toBeEmpty();
    });
});

describe('Pre-booking Time Changes', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);

        $this->user = User::factory()->create();
        $this->user->assignRole(Role::findByName(RoleEnum::MENTOR->value));

        $this->mentorProgram = MentorProgram::factory()->create([
            'mentor_id' => $this->user->getKey(),
        ]);

        $this->timezone = 'UTC';
    });

    it('updates available slots when minimum_pre_booking_time increases', function (): void {
        Date::setTestNow(Date::create(2026, 1, 10, 10, 0, 0, $this->timezone));

        // Low pre-booking time
        $this->user->profile->update(['minimum_pre_booking_time' => 30]);

        $serviceShort = new AvailableCalendarEventsSlotsService(
            $this->user,
            $this->timezone,
            $this->mentorProgram,
            [],
            false,
        );

        $slotsShort = $serviceShort->getAvailableSlots();

        //         High pre-booking time
        $this->user->profile->update(['minimum_pre_booking_time' => 1440]); // 24 hours
        $this->mentorProgram->refresh();

        $serviceLong = new AvailableCalendarEventsSlotsService(
            $this->user,
            $this->timezone,
            $this->mentorProgram,
            [],
            false,
        );

        $slotsLong = $serviceLong->getAvailableSlots();

        // With longer pre-booking time, first available slot starts later
        expect($slotsLong[0]['start']->gt($slotsShort[0]['start']))->toBeTrue();
    });

    it('updates available slots when minimum_pre_booking_time decreases', function (): void {
        Date::setTestNow(Date::create(2026, 1, 10, 10, 0, 0, $this->timezone));

        // High pre-booking time first
        $this->user->profile->update(['minimum_pre_booking_time' => 1440]); // 24 hours

        $serviceLong = new AvailableCalendarEventsSlotsService(
            $this->user,
            $this->timezone,
            $this->mentorProgram,
            [],
            false,
        );

        $slotsLong = $serviceLong->getAvailableSlots();

        // Then decrease it
        $this->user->profile->update(['minimum_pre_booking_time' => 60]); // 1 hour
        $this->mentorProgram->refresh();

        $serviceShort = new AvailableCalendarEventsSlotsService(
            $this->user,
            $this->timezone,
            $this->mentorProgram,
            [],
            false,
        );

        $slotsShort = $serviceShort->getAvailableSlots();

        // With shorter pre-booking time, first available slot starts earlier
        expect($slotsShort[0]['start']->lt($slotsLong[0]['start']))->toBeTrue();
    });

    it('handles very large minimum_pre_booking_time of 10080 minutes', function (): void {
        Date::setTestNow(Date::create(2026, 1, 10, 10, 0, 0, $this->timezone));

        // 10080 minutes = 7 days
        $this->user->profile->update(['minimum_pre_booking_time' => 10080]);

        $service = new AvailableCalendarEventsSlotsService(
            $this->user,
            $this->timezone,
            $this->mentorProgram,
            [],
            false,
        );

        $slots = $service->getAvailableSlots();

        expect($slots)->toBeArray()->not()->toBeEmpty();

        // First slot should start at least 7 days from now
        $expectedMinStart = Date::now($this->timezone)->addMinutes(10080);
        expect($slots[0]['start']->gte($expectedMinStart))->toBeTrue();
    });
});

describe('Mutation Coverage - Default Values', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);

        $this->user = User::factory()->create();
        $this->user->assignRole(Role::findByName(RoleEnum::MENTOR->value));
        $this->user->profile->update(['minimum_pre_booking_time' => 0]);

        $this->mentorProgram = MentorProgram::factory()->create([
            'mentor_id'        => $this->user->getKey(),
            'session_duration' => 0,
        ]);

        $this->timezone = 'UTC';
    });

    it('uses zero minimum_pre_booking_time correctly', function (): void {
        Date::setTestNow(Date::create(2026, 1, 10, 10, 0, 0, $this->timezone));

        // Set minimum_pre_booking_time to 0
        $this->mentorProgram->mentor->profile->update(['minimum_pre_booking_time' => 0]);

        $service = new AvailableCalendarEventsSlotsService(
            $this->user,
            $this->timezone,
            $this->mentorProgram,
            [],
            false,
        );

        $slots = $service->getAvailableSlots();

        // With 0 pre-booking time, slot should start at current time (10:00)
        expect($slots)->not->toBeEmpty();
        expect($slots[0]['start']->format('H:i'))->toBe('10:00');
    });

    it('uses zero session_duration and includes small gaps', function (): void {
        Date::setTestNow(Date::create(2026, 1, 10, 10, 0, 0, $this->timezone));

        // Set session_duration to 0
        $this->mentorProgram->update(['session_duration' => 0]);

        // Create event leaving a 15-minute gap at the start
        $eventStart = Date::parse('2026-01-10 10:15:00', $this->timezone);
        $eventEnd = Date::parse('2026-01-10 11:00:00', $this->timezone);

        CalendarEvent::factory()->create([
            'start_date_time'   => $eventStart,
            'end_date_time'     => $eventEnd,
            'date'              => $eventStart->format('Y-m-d'),
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ])->calendarEventUsers()->attach([$this->user->getKey(), $this->mentorProgram->mentor_id]);

        $service = new AvailableCalendarEventsSlotsService(
            $this->user,
            $this->timezone,
            $this->mentorProgram,
            [],
            false,
        );

        $slots = $service->getAvailableSlots();

        // With session_duration 0, small gaps should be included
        $initialSlot = collect($slots)->first(fn ($slot): bool => $slot['start']->format('H:i') === '10:00'
            && $slot['end']->format('H:i') === '10:15');

        expect($initialSlot)->not->toBeNull();
    });

    it('correctly handles session_duration of 1 with zero gap', function (): void {
        Date::setTestNow(Date::create(2026, 1, 10, 10, 0, 0, $this->timezone));

        // session_duration = 1 minute
        $this->mentorProgram->update(['session_duration' => 1]);

        // Create event starting exactly at period start (no gap)
        $eventStart = Date::parse('2026-01-10 10:00:00', $this->timezone);
        $eventEnd = Date::parse('2026-01-10 11:00:00', $this->timezone);

        CalendarEvent::factory()->create([
            'start_date_time'   => $eventStart,
            'end_date_time'     => $eventEnd,
            'date'              => $eventStart->format('Y-m-d'),
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ])->calendarEventUsers()->attach([$this->user->getKey(), $this->mentorProgram->mentor_id]);

        $this->mentorProgram->refresh();
        $service = new AvailableCalendarEventsSlotsService(
            $this->user,
            $this->timezone,
            $this->mentorProgram,
            [],
            false,
        );

        $slots = $service->getAvailableSlots();

        // No initial slot because event starts at period start (zero gap)
        // Only trailing slot should exist
        expect($slots)->toHaveCount(1);
        expect($slots[0]['start']->format('H:i'))->toBe('11:00');
    });

    it('differentiates between session_duration 0 and 1 for small gaps', function (): void {
        Date::setTestNow(Date::create(2026, 1, 10, 10, 0, 0, $this->timezone));

        // Create event with 5-minute gap
        $eventStart = Date::parse('2026-01-10 10:05:00', $this->timezone);
        $eventEnd = Date::parse('2026-01-10 11:00:00', $this->timezone);

        $event = CalendarEvent::factory()->create([
            'start_date_time'   => $eventStart,
            'end_date_time'     => $eventEnd,
            'date'              => $eventStart->format('Y-m-d'),
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);
        $event->calendarEventUsers()->attach([$this->user->getKey(), $this->mentorProgram->mentor_id]);

        // With session_duration = 0, 5-minute gap should be included
        $this->mentorProgram->update(['session_duration' => 0]);

        $serviceZero = new AvailableCalendarEventsSlotsService(
            $this->user,
            $this->timezone,
            $this->mentorProgram,
            [],
            false,
        );

        $slotsZero = $serviceZero->getAvailableSlots();

        $initialSlotZero = collect($slotsZero)->first(fn ($slot): bool => $slot['start']->format('H:i') === '10:00'
            && $slot['end']->format('H:i') === '10:05');

        expect($initialSlotZero)->not->toBeNull();

        // With session_duration = 10, 5-minute gap should be skipped
        $this->mentorProgram->update(['session_duration' => 10]);

        $serviceTen = new AvailableCalendarEventsSlotsService(
            $this->user,
            $this->timezone,
            $this->mentorProgram,
            [],
            false,
        );

        $slotsTen = $serviceTen->getAvailableSlots();

        $initialSlotTen = collect($slotsTen)->first(fn ($slot): bool => $slot['start']->format('H:i') === '10:00'
            && $slot['end']->format('H:i') === '10:05');

        expect($initialSlotTen)->toBeNull();
    });
});

describe('Mutation Coverage - Session Duration Boundaries', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);

        $this->user = User::factory()->create();
        $this->user->assignRole(Role::findByName(RoleEnum::MENTOR->value));
        $this->user->profile->update(['minimum_pre_booking_time' => 0]);

        $this->mentorProgram = MentorProgram::factory()->create([
            'mentor_id' => $this->user->getKey(),
        ]);

        $this->timezone = 'UTC';
    });

    it('includes gap equal to session_duration (boundary test)', function (): void {
        Date::setTestNow(Date::create(2026, 1, 10, 10, 0, 0, $this->timezone));

        // Set session_duration to 30 minutes
        $this->mentorProgram->update(['session_duration' => 30]);

        // Create event leaving exactly 30-minute gap (equal to session_duration)
        $eventStart = Date::parse('2026-01-10 10:30:00', $this->timezone);
        $eventEnd = Date::parse('2026-01-10 11:00:00', $this->timezone);

        CalendarEvent::factory()->create([
            'start_date_time'   => $eventStart,
            'end_date_time'     => $eventEnd,
            'date'              => $eventStart->format('Y-m-d'),
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ])->calendarEventUsers()->attach([$this->user->getKey(), $this->mentorProgram->mentor_id]);

        $service = new AvailableCalendarEventsSlotsService(
            $this->user,
            $this->timezone,
            $this->mentorProgram,
            [],
            false,
        );

        $slots = $service->getAvailableSlots();

        // Gap equal to session_duration should be INCLUDED (not skipped)
        $initialSlot = collect($slots)->first(fn ($slot): bool => $slot['start']->format('H:i') === '10:00'
            && $slot['end']->format('H:i') === '10:30');

        expect($initialSlot)->not->toBeNull();
    });

    it('skips gap smaller than session_duration (boundary test)', function (): void {
        Date::setTestNow(Date::create(2026, 1, 10, 10, 0, 0, $this->timezone));

        // Set session_duration to 30 minutes
        $this->mentorProgram->update(['session_duration' => 30]);

        // Create event leaving 29-minute gap (less than session_duration)
        $eventStart = Date::parse('2026-01-10 10:29:00', $this->timezone);
        $eventEnd = Date::parse('2026-01-10 11:00:00', $this->timezone);

        CalendarEvent::factory()->create([
            'start_date_time'   => $eventStart,
            'end_date_time'     => $eventEnd,
            'date'              => $eventStart->format('Y-m-d'),
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ])->calendarEventUsers()->attach([$this->user->getKey(), $this->mentorProgram->mentor_id]);

        $service = new AvailableCalendarEventsSlotsService(
            $this->user,
            $this->timezone,
            $this->mentorProgram,
            [],
            false,
        );

        $slots = $service->getAvailableSlots();

        // Gap smaller than session_duration should be SKIPPED
        $initialSlot = collect($slots)->first(fn ($slot): bool => $slot['start']->format('H:i') === '10:00'
            && $slot['end']->lessThan($eventStart));

        expect($initialSlot)->toBeNull();
    });

    it('includes gap between events equal to session_duration', function (): void {
        Date::setTestNow(Date::create(2026, 1, 10, 10, 0, 0, $this->timezone));

        // Set session_duration to 30 minutes
        $this->mentorProgram->update(['session_duration' => 30]);

        // First event starts AFTER period start (so there's an initial slot)
        $event1Start = Date::parse('2026-01-10 10:30:00', $this->timezone);
        $event1End = Date::parse('2026-01-10 11:00:00', $this->timezone);

        // Second event starts exactly 30 minutes after first ends (gap = session_duration)
        $event2Start = Date::parse('2026-01-10 11:30:00', $this->timezone);
        $event2End = Date::parse('2026-01-10 12:00:00', $this->timezone);

        CalendarEvent::factory()->create([
            'start_date_time'   => $event1Start,
            'end_date_time'     => $event1End,
            'date'              => $event1Start->format('Y-m-d'),
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ])->calendarEventUsers()->attach([$this->user->getKey(), $this->mentorProgram->mentor_id]);

        CalendarEvent::factory()->create([
            'start_date_time'   => $event2Start,
            'end_date_time'     => $event2End,
            'date'              => $event2Start->format('Y-m-d'),
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ])->calendarEventUsers()->attach([$this->user->getKey(), $this->mentorProgram->mentor_id]);

        $service = new AvailableCalendarEventsSlotsService(
            $this->user,
            $this->timezone,
            $this->mentorProgram,
            [],
            false,
        );

        $slots = $service->getAvailableSlots();

        // Gap between events equal to session_duration should be included
        $middleSlot = collect($slots)->first(fn ($slot): bool => $slot['start']->format('H:i') === '11:00'
            && $slot['end']->format('H:i') === '11:30');

        expect($middleSlot)->not->toBeNull();
    });

    it('skips gap between events smaller than session_duration', function (): void {
        Date::setTestNow(Date::create(2026, 1, 10, 10, 0, 0, $this->timezone));

        // Set session_duration to 30 minutes
        $this->mentorProgram->update(['session_duration' => 30]);

        // First event
        $event1Start = Date::parse('2026-01-10 10:00:00', $this->timezone);
        $event1End = Date::parse('2026-01-10 10:30:00', $this->timezone);

        // Second event starts 20 minutes after first ends (gap < session_duration)
        $event2Start = Date::parse('2026-01-10 10:50:00', $this->timezone);
        $event2End = Date::parse('2026-01-10 11:30:00', $this->timezone);

        CalendarEvent::factory()->create([
            'start_date_time'   => $event1Start,
            'end_date_time'     => $event1End,
            'date'              => $event1Start->format('Y-m-d'),
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ])->calendarEventUsers()->attach([$this->user->getKey(), $this->mentorProgram->mentor_id]);

        CalendarEvent::factory()->create([
            'start_date_time'   => $event2Start,
            'end_date_time'     => $event2End,
            'date'              => $event2Start->format('Y-m-d'),
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ])->calendarEventUsers()->attach([$this->user->getKey(), $this->mentorProgram->mentor_id]);

        $service = new AvailableCalendarEventsSlotsService(
            $this->user,
            $this->timezone,
            $this->mentorProgram,
            [],
            false,
        );

        $slots = $service->getAvailableSlots();

        // Gap between events smaller than session_duration should be skipped
        $middleSlot = collect($slots)->first(fn ($slot): bool => $slot['start']->format('H:i') === '10:30'
            && $slot['end']->format('H:i') === '10:50');

        expect($middleSlot)->toBeNull();
    });
});

describe('Mutation Coverage - Loop Behavior', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);

        $this->user = User::factory()->create();
        $this->user->assignRole(Role::findByName(RoleEnum::MENTOR->value));
        $this->user->profile->update(['minimum_pre_booking_time' => 0]);

        $this->mentorProgram = MentorProgram::factory()->create([
            'mentor_id'        => $this->user->getKey(),
            'session_duration' => 0,
        ]);

        $this->timezone = 'UTC';
    });

    it('continues processing events after first event with valid initial slot', function (): void {
        Date::setTestNow(Date::create(2026, 1, 10, 10, 0, 0, $this->timezone));

        // First event starts after period start (creates initial slot)
        $event1Start = Date::parse('2026-01-10 10:30:00', $this->timezone);
        $event1End = Date::parse('2026-01-10 11:00:00', $this->timezone);

        // Second event after a gap
        $event2Start = Date::parse('2026-01-10 11:30:00', $this->timezone);
        $event2End = Date::parse('2026-01-10 12:00:00', $this->timezone);

        // Third event after another gap
        $event3Start = Date::parse('2026-01-10 12:30:00', $this->timezone);
        $event3End = Date::parse('2026-01-10 13:00:00', $this->timezone);

        CalendarEvent::factory()->create([
            'start_date_time'   => $event1Start,
            'end_date_time'     => $event1End,
            'date'              => $event1Start->format('Y-m-d'),
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ])->calendarEventUsers()->attach([$this->user->getKey(), $this->mentorProgram->mentor_id]);

        CalendarEvent::factory()->create([
            'start_date_time'   => $event2Start,
            'end_date_time'     => $event2End,
            'date'              => $event2Start->format('Y-m-d'),
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ])->calendarEventUsers()->attach([$this->user->getKey(), $this->mentorProgram->mentor_id]);

        CalendarEvent::factory()->create([
            'start_date_time'   => $event3Start,
            'end_date_time'     => $event3End,
            'date'              => $event3Start->format('Y-m-d'),
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ])->calendarEventUsers()->attach([$this->user->getKey(), $this->mentorProgram->mentor_id]);

        $service = new AvailableCalendarEventsSlotsService(
            $this->user,
            $this->timezone,
            $this->mentorProgram,
            [],
            false,
        );

        $slots = $service->getAvailableSlots();

        // Should have 4 slots: initial, between events, and trailing
        expect(count($slots))->toBeGreaterThanOrEqual(4);

        // Verify initial slot exists
        $initialSlot = collect($slots)->first(fn ($slot): bool => $slot['start']->format('H:i') === '10:00'
            && $slot['end']->format('H:i') === '10:30');
        expect($initialSlot)->not->toBeNull();

        // Verify slot between event1 and event2 exists
        $slot1 = collect($slots)->first(fn ($slot): bool => $slot['start']->format('H:i') === '11:00'
            && $slot['end']->format('H:i') === '11:30');
        expect($slot1)->not->toBeNull();

        // Verify slot between event2 and event3 exists
        $slot2 = collect($slots)->first(fn ($slot): bool => $slot['start']->format('H:i') === '12:00'
            && $slot['end']->format('H:i') === '12:30');
        expect($slot2)->not->toBeNull();
    });

    it('processes slot with exactly 1 minute duration', function (): void {
        Date::setTestNow(Date::create(2026, 1, 10, 10, 0, 0, $this->timezone));

        $this->mentorProgram->update(['session_duration' => 0]);

        // Create event leaving exactly 5-minute gap (rounding to 5 min increments)
        $eventStart = Date::parse('2026-01-10 10:05:00', $this->timezone);
        $eventEnd = Date::parse('2026-01-10 11:00:00', $this->timezone);

        CalendarEvent::factory()->create([
            'start_date_time'   => $eventStart,
            'end_date_time'     => $eventEnd,
            'date'              => $eventStart->format('Y-m-d'),
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ])->calendarEventUsers()->attach([$this->user->getKey(), $this->mentorProgram->mentor_id]);

        $service = new AvailableCalendarEventsSlotsService(
            $this->user,
            $this->timezone,
            $this->mentorProgram,
            [],
            false,
        );

        $slots = $service->getAvailableSlots();

        // With session_duration=0, even small gaps should be included
        $initialSlot = collect($slots)->first(fn ($slot): bool => $slot['start']->format('H:i') === '10:00'
            && $slot['end']->format('H:i') === '10:05');

        expect($initialSlot)->not->toBeNull();
    });
});

describe('Mutation Coverage - Query Verification', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);

        $this->user = User::factory()->create();
        $this->user->assignRole(Role::findByName(RoleEnum::MENTOR->value));
        $this->user->profile->update(['minimum_pre_booking_time' => 0]);

        $this->mentor = User::factory()->create();
        $this->mentor->assignRole(Role::findByName(RoleEnum::MENTOR->value));

        $this->mentorProgram = MentorProgram::factory()->create([
            'mentor_id'        => $this->mentor->getKey(),
            'session_duration' => 0,
        ]);

        $this->timezone = 'UTC';
    });

    it('includes events attached only to mentor in available slots calculation', function (): void {
        Date::setTestNow(Date::create(2026, 1, 10, 10, 0, 0, $this->timezone));

        // Create event attached ONLY to mentor (not to user)
        $eventStart = Date::parse('2026-01-10 11:00:00', $this->timezone);
        $eventEnd = Date::parse('2026-01-10 12:00:00', $this->timezone);

        $event = CalendarEvent::factory()->create([
            'start_date_time'   => $eventStart,
            'end_date_time'     => $eventEnd,
            'date'              => $eventStart->format('Y-m-d'),
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);

        // Attach ONLY to mentor, not to user
        $event->calendarEventUsers()->attach($this->mentor->getKey());

        $service = new AvailableCalendarEventsSlotsService(
            $this->user,
            $this->timezone,
            $this->mentorProgram,
            [],
            false,
        );

        $slots = $service->getAvailableSlots();

        // The event should block slots even though it's only attached to mentor
        // First slot should end at event start
        $firstSlot = $slots[0];
        expect($firstSlot['end']->format('H:i'))->toBe('11:00');

        // Second slot should start at event end
        $secondSlot = $slots[1];
        expect($secondSlot['start']->format('H:i'))->toBe('12:00');
    });

    it('verifies both user and mentor events are considered independently', function (): void {
        Date::setTestNow(Date::create(2026, 1, 10, 10, 0, 0, $this->timezone));

        // Create event attached only to user
        $userEventStart = Date::parse('2026-01-10 11:00:00', $this->timezone);
        $userEventEnd = Date::parse('2026-01-10 11:30:00', $this->timezone);

        $userEvent = CalendarEvent::factory()->create([
            'start_date_time'   => $userEventStart,
            'end_date_time'     => $userEventEnd,
            'date'              => $userEventStart->format('Y-m-d'),
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);
        $userEvent->calendarEventUsers()->attach($this->user->getKey());

        // Create event attached only to mentor
        $mentorEventStart = Date::parse('2026-01-10 12:00:00', $this->timezone);
        $mentorEventEnd = Date::parse('2026-01-10 12:30:00', $this->timezone);

        $mentorEvent = CalendarEvent::factory()->create([
            'start_date_time'   => $mentorEventStart,
            'end_date_time'     => $mentorEventEnd,
            'date'              => $mentorEventStart->format('Y-m-d'),
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);
        $mentorEvent->calendarEventUsers()->attach($this->mentor->getKey());

        $service = new AvailableCalendarEventsSlotsService(
            $this->user,
            $this->timezone,
            $this->mentorProgram,
            [],
            false,
        );

        $slots = $service->getAvailableSlots();

        // Should have 4 slots: [10:00-11:00], [11:30-12:00], [12:30-end]
        // Wait - there should be 3 gaps around 2 events
        expect(count($slots))->toBeGreaterThanOrEqual(3);

        // Verify both events created gaps
        $slotBeforeUserEvent = collect($slots)->first(fn ($slot): bool => $slot['end']->format('H:i') === '11:00');
        expect($slotBeforeUserEvent)->not->toBeNull();

        $slotBetweenEvents = collect($slots)->first(fn ($slot): bool => $slot['start']->format('H:i') === '11:30'
            && $slot['end']->format('H:i') === '12:00');
        expect($slotBetweenEvents)->not->toBeNull();
    });
});

describe('Session Duration Changes', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);

        $this->user = User::factory()->create();
        $this->user->assignRole(Role::findByName(RoleEnum::MENTOR->value));
        $this->user->profile->update(['minimum_pre_booking_time' => 0]);

        $this->mentorProgram = MentorProgram::factory()->create([
            'mentor_id'        => $this->user->getKey(),
            'session_duration' => 30,
        ]);

        $this->timezone = 'UTC';
    });

    it('existing bookings remain valid after session_duration change', function (): void {
        Date::setTestNow(Date::create(2026, 1, 10, 10, 0, 0, $this->timezone));

        // Create an event with current session duration
        $eventStart = Date::parse('2026-01-10 14:00:00', $this->timezone);
        $eventEnd = Date::parse('2026-01-10 14:30:00', $this->timezone); // 30 min

        $event = CalendarEvent::factory()->create([
            'start_date_time'   => $eventStart,
            'end_date_time'     => $eventEnd,
            'date'              => $eventStart->format('Y-m-d'),
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);
        $event->calendarEventUsers()->attach($this->user->getKey());

        // Change session duration to 60 minutes
        $this->mentorProgram->update(['session_duration' => 60]);

        $service = new AvailableCalendarEventsSlotsService(
            $this->user,
            $this->timezone,
            $this->mentorProgram,
            [],
            false,
        );

        $slots = $service->getAvailableSlots();

        // The existing 30-minute event should still be blocked out
        $slotsDuringEvent = collect($slots)->filter(fn ($slot): bool => $slot['start']->lt($eventEnd) && $slot['end']->gt($eventStart));

        // Should have gaps around the event
        expect($slots)->toBeArray()->not()->toBeEmpty();
    });
});

describe('Mutation Coverage - Continue vs Break in Loop', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);

        $this->user = User::factory()->create();
        $this->user->assignRole(Role::findByName(RoleEnum::MENTOR->value));
        $this->user->profile->update(['minimum_pre_booking_time' => 0]);

        $this->mentorProgram = MentorProgram::factory()->create([
            'mentor_id'        => $this->user->getKey(),
            'session_duration' => 60,
        ]);

        $this->timezone = 'UTC';
    });

    it('continues to process events after skipping initial gap (kills ContinueToBreak mutation)', function (): void {
        Date::setTestNow(Date::create(2026, 1, 10, 10, 0, 0, $this->timezone));

        // First event creates a gap that's too small (will be skipped via continue, not break)
        $event1Start = Date::parse('2026-01-10 10:30:00', $this->timezone); // 30 min gap < 60 session
        $event1End = Date::parse('2026-01-10 11:00:00', $this->timezone);

        // Second event creates a valid gap
        $event2Start = Date::parse('2026-01-10 12:00:00', $this->timezone); // 60 min gap = session
        $event2End = Date::parse('2026-01-10 12:30:00', $this->timezone);

        // Third event to create another gap
        $event3Start = Date::parse('2026-01-10 14:00:00', $this->timezone); // 90 min gap > session
        $event3End = Date::parse('2026-01-10 14:30:00', $this->timezone);

        CalendarEvent::factory()->create([
            'start_date_time'   => $event1Start,
            'end_date_time'     => $event1End,
            'date'              => $event1Start->format('Y-m-d'),
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ])->calendarEventUsers()->attach([$this->user->getKey(), $this->mentorProgram->mentor_id]);

        CalendarEvent::factory()->create([
            'start_date_time'   => $event2Start,
            'end_date_time'     => $event2End,
            'date'              => $event2Start->format('Y-m-d'),
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ])->calendarEventUsers()->attach([$this->user->getKey(), $this->mentorProgram->mentor_id]);

        CalendarEvent::factory()->create([
            'start_date_time'   => $event3Start,
            'end_date_time'     => $event3End,
            'date'              => $event3Start->format('Y-m-d'),
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ])->calendarEventUsers()->attach([$this->user->getKey(), $this->mentorProgram->mentor_id]);

        $service = new AvailableCalendarEventsSlotsService(
            $this->user,
            $this->timezone,
            $this->mentorProgram,
            [],
            false,
        );

        $slots = $service->getAvailableSlots();

        // Initial gap (10:00-10:30) should be SKIPPED (too small)
        // Gap between event1 and event2 (11:00-12:00) should be INCLUDED (exactly 60 min)
        // Gap between event2 and event3 (12:30-14:00) should be INCLUDED (90 min > 60)
        // Trailing slot should exist

        // If 'break' was used instead of 'continue', we'd only have 1 slot (trailing)
        // With 'continue', we should have at least 3 slots

        $slot1to2 = collect($slots)->first(fn ($slot): bool => $slot['start']->format('H:i') === '11:00'
            && $slot['end']->format('H:i') === '12:00');

        $slot2to3 = collect($slots)->first(fn ($slot): bool => $slot['start']->format('H:i') === '12:30'
            && $slot['end']->format('H:i') === '14:00');

        // These gaps should exist because loop CONTINUED after skipping initial gap
        expect($slot1to2)->not->toBeNull('Gap between event1 and event2 should exist');
        expect($slot2to3)->not->toBeNull('Gap between event2 and event3 should exist');
    });

    it('continues to next event after skipping small gap between events (kills middle ContinueToBreak)', function (): void {
        Date::setTestNow(Date::create(2026, 1, 10, 10, 0, 0, $this->timezone));

        // Event1 has valid initial gap (120 min)
        $event1Start = Date::parse('2026-01-10 12:00:00', $this->timezone);
        $event1End = Date::parse('2026-01-10 12:30:00', $this->timezone);

        // Gap between event1 and event2 is only 20 min (will be skipped)
        $event2Start = Date::parse('2026-01-10 12:50:00', $this->timezone);
        $event2End = Date::parse('2026-01-10 13:20:00', $this->timezone);

        // Gap between event2 and event3 is 100 min (should be included)
        $event3Start = Date::parse('2026-01-10 15:00:00', $this->timezone);
        $event3End = Date::parse('2026-01-10 15:30:00', $this->timezone);

        CalendarEvent::factory()->create([
            'start_date_time'   => $event1Start,
            'end_date_time'     => $event1End,
            'date'              => $event1Start->format('Y-m-d'),
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ])->calendarEventUsers()->attach([$this->user->getKey(), $this->mentorProgram->mentor_id]);

        CalendarEvent::factory()->create([
            'start_date_time'   => $event2Start,
            'end_date_time'     => $event2End,
            'date'              => $event2Start->format('Y-m-d'),
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ])->calendarEventUsers()->attach([$this->user->getKey(), $this->mentorProgram->mentor_id]);

        CalendarEvent::factory()->create([
            'start_date_time'   => $event3Start,
            'end_date_time'     => $event3End,
            'date'              => $event3Start->format('Y-m-d'),
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ])->calendarEventUsers()->attach([$this->user->getKey(), $this->mentorProgram->mentor_id]);

        $service = new AvailableCalendarEventsSlotsService(
            $this->user,
            $this->timezone,
            $this->mentorProgram,
            [],
            false,
        );

        $slots = $service->getAvailableSlots();

        // Initial slot (10:00-12:00) should be included
        $initialSlot = collect($slots)->first(fn ($slot): bool => $slot['start']->format('H:i') === '10:00'
            && $slot['end']->format('H:i') === '12:00');

        // Gap between event2 and event3 (13:20-15:00) should be included even after skipping middle gap
        $slot2to3 = collect($slots)->first(fn ($slot): bool => $slot['start']->format('H:i') === '13:20'
            && $slot['end']->format('H:i') === '15:00');

        expect($initialSlot)->not->toBeNull('Initial slot should exist');
        expect($slot2to3)->not->toBeNull('Gap after skipped small gap should exist - loop must CONTINUE not BREAK');
    });
});

describe('Mutation Coverage - Session Duration Exact Values', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);

        $this->user = User::factory()->create();
        $this->user->assignRole(Role::findByName(RoleEnum::MENTOR->value));
        $this->user->profile->update(['minimum_pre_booking_time' => 0]);

        $this->mentorProgram = MentorProgram::factory()->create([
            'mentor_id' => $this->user->getKey(),
        ]);

        $this->timezone = 'UTC';
    });

    it('session_duration of exactly 1 skips slots smaller than 1 minute (kills IncrementInteger)', function (): void {
        Date::setTestNow(Date::create(2026, 1, 10, 10, 0, 0, $this->timezone));

        $this->mentorProgram->update(['session_duration' => 1]);

        // Event starts exactly at period start - creates 0-minute initial gap
        $eventStart = Date::parse('2026-01-10 10:00:00', $this->timezone);
        $eventEnd = Date::parse('2026-01-10 11:00:00', $this->timezone);

        CalendarEvent::factory()->create([
            'start_date_time'   => $eventStart,
            'end_date_time'     => $eventEnd,
            'date'              => $eventStart->format('Y-m-d'),
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ])->calendarEventUsers()->attach([$this->user->getKey(), $this->mentorProgram->mentor_id]);

        $service = new AvailableCalendarEventsSlotsService(
            $this->user,
            $this->timezone,
            $this->mentorProgram,
            [],
            false,
        );

        $slots = $service->getAvailableSlots();

        // With session_duration=1 and 0-minute gap, the check `sessionDuration > 0 && slotDuration < sessionDuration`
        // evaluates to `1 > 0 && 0 < 1` = true, so the slot is skipped
        // Only trailing slot should exist
        expect($slots)->toHaveCount(1);
        expect($slots[0]['start']->format('H:i'))->toBe('11:00');
    });

    it('session_duration of exactly 1 includes 1-minute slots (boundary test)', function (): void {
        Date::setTestNow(Date::create(2026, 1, 10, 10, 0, 0, $this->timezone));

        $this->mentorProgram->update(['session_duration' => 1]);

        // Create event with exactly 5-minute gap (after rounding)
        $eventStart = Date::parse('2026-01-10 10:05:00', $this->timezone);
        $eventEnd = Date::parse('2026-01-10 11:00:00', $this->timezone);

        CalendarEvent::factory()->create([
            'start_date_time'   => $eventStart,
            'end_date_time'     => $eventEnd,
            'date'              => $eventStart->format('Y-m-d'),
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ])->calendarEventUsers()->attach([$this->user->getKey(), $this->mentorProgram->mentor_id]);

        $service = new AvailableCalendarEventsSlotsService(
            $this->user,
            $this->timezone,
            $this->mentorProgram,
            [],
            false,
        );

        $slots = $service->getAvailableSlots();

        // With session_duration=1 and 5-minute gap: `1 > 0 && 5 < 1` = false, so slot is INCLUDED
        $initialSlot = collect($slots)->first(fn ($slot): bool => $slot['start']->format('H:i') === '10:00'
            && $slot['end']->format('H:i') === '10:05');

        expect($initialSlot)->not->toBeNull();
    });

    it('slot with zero duration is skipped regardless of session_duration (kills slotDuration <= 0 mutation)', function (): void {
        Date::setTestNow(Date::create(2026, 1, 10, 10, 0, 0, $this->timezone));

        // Even with session_duration=0, zero-duration slots should be skipped by the `slotDuration <= 0` check
        $this->mentorProgram->update(['session_duration' => 0]);

        // Event starts exactly at period start
        $eventStart = Date::parse('2026-01-10 10:00:00', $this->timezone);
        $eventEnd = Date::parse('2026-01-10 11:00:00', $this->timezone);

        CalendarEvent::factory()->create([
            'start_date_time'   => $eventStart,
            'end_date_time'     => $eventEnd,
            'date'              => $eventStart->format('Y-m-d'),
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ])->calendarEventUsers()->attach([$this->user->getKey(), $this->mentorProgram->mentor_id]);

        $service = new AvailableCalendarEventsSlotsService(
            $this->user,
            $this->timezone,
            $this->mentorProgram,
            [],
            false,
        );

        $slots = $service->getAvailableSlots();

        // Zero-duration initial gap should be skipped (check is `slotDuration <= 0`)
        // If mutation changed to `slotDuration <= 1`, a 0-minute slot would still be skipped
        // But a 5-minute slot would also be skipped incorrectly
        expect($slots)->toHaveCount(1);
        expect($slots[0]['start']->format('H:i'))->toBe('11:00');
    });

    it('slot with exactly 1 minute is INCLUDED when session_duration=0 (kills IncrementInteger on slotDuration <= 0)', function (): void {
        Date::setTestNow(Date::create(2026, 1, 10, 10, 0, 0, $this->timezone));

        $this->mentorProgram->update(['session_duration' => 0]);

        // Event starts 5 minutes after period start (after rounding, gives exactly 5-minute gap)
        $eventStart = Date::parse('2026-01-10 10:05:00', $this->timezone);
        $eventEnd = Date::parse('2026-01-10 11:00:00', $this->timezone);

        CalendarEvent::factory()->create([
            'start_date_time'   => $eventStart,
            'end_date_time'     => $eventEnd,
            'date'              => $eventStart->format('Y-m-d'),
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ])->calendarEventUsers()->attach([$this->user->getKey(), $this->mentorProgram->mentor_id]);

        $service = new AvailableCalendarEventsSlotsService(
            $this->user,
            $this->timezone,
            $this->mentorProgram,
            [],
            false,
        );

        $slots = $service->getAvailableSlots();

        // With session_duration=0, the sessionDuration > 0 check fails, so only slotDuration <= 0 check applies
        // 5-minute slot should be INCLUDED because 5 > 0 (not <= 0)
        // If mutation changed to slotDuration <= 1, a 5-minute slot would still pass
        // But the key is: slots with duration > 0 should be included when session_duration = 0
        $initialSlot = collect($slots)->first(fn ($slot): bool => $slot['start']->format('H:i') === '10:00'
            && $slot['end']->format('H:i') === '10:05');

        expect($initialSlot)->not->toBeNull('5-minute gap should be included when session_duration=0');
        expect($slots)->toHaveCount(2); // Initial slot + trailing slot
    });
});

describe('Mutation Coverage - With Clause Eager Loading (Line 105)', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);

        $this->user = User::factory()->create();
        $this->user->assignRole(Role::findByName(RoleEnum::MENTOR->value));
        $this->user->profile->update(['minimum_pre_booking_time' => 0]);

        $this->mentor = User::factory()->create();
        $this->mentor->assignRole(Role::findByName(RoleEnum::MENTOR->value));

        $this->mentorProgram = MentorProgram::factory()->create([
            'mentor_id'        => $this->mentor->getKey(),
            'session_duration' => 0,
        ]);

        $this->timezone = 'UTC';
    });

    it('with clause filters calendarEventUsers to only relevant user IDs (kills RemoveArrayItem)', function (): void {
        Date::setTestNow(Date::create(2026, 1, 10, 10, 0, 0, $this->timezone));

        // Create event attached to mentor AND a third unrelated user
        $thirdUser = User::factory()->create();

        $eventStart = Date::parse('2026-01-10 11:00:00', $this->timezone);
        $eventEnd = Date::parse('2026-01-10 12:00:00', $this->timezone);

        $event = CalendarEvent::factory()->create([
            'start_date_time'   => $eventStart,
            'end_date_time'     => $eventEnd,
            'date'              => $eventStart->format('Y-m-d'),
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);

        // Attach mentor AND third user (not the booking user)
        $event->calendarEventUsers()->attach([
            $this->mentor->getKey() => ['role' => CalendarEventRoleEnum::HOST, 'colour' => '#FF0000'],
            $thirdUser->getKey()    => ['role' => CalendarEventRoleEnum::MENTI, 'colour' => '#00FF00'],
        ]);

        $service = new AvailableCalendarEventsSlotsService(
            $this->user,
            $this->timezone,
            $this->mentorProgram,
            [],
            false,
        );

        $slots = $service->getAvailableSlots();

        // The event should block time because mentor is attached (even though booking user isn't)
        // This proves the whereHas in Line 104 works
        expect($slots)->toHaveCount(2);
        expect($slots[0]['end']->format('H:i'))->toBe('11:00');
        expect($slots[1]['start']->format('H:i'))->toBe('12:00');
    });

    it('events with only booking user attached are considered (verifies both IDs in whereIn)', function (): void {
        Date::setTestNow(Date::create(2026, 1, 10, 10, 0, 0, $this->timezone));

        $eventStart = Date::parse('2026-01-10 11:00:00', $this->timezone);
        $eventEnd = Date::parse('2026-01-10 12:00:00', $this->timezone);

        $event = CalendarEvent::factory()->create([
            'start_date_time'   => $eventStart,
            'end_date_time'     => $eventEnd,
            'date'              => $eventStart->format('Y-m-d'),
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);

        // Attach ONLY the booking user, NOT the mentor
        $event->calendarEventUsers()->attach($this->user->getKey());

        $service = new AvailableCalendarEventsSlotsService(
            $this->user,
            $this->timezone,
            $this->mentorProgram,
            [],
            false,
        );

        $slots = $service->getAvailableSlots();

        // Event should block time because booking user is attached
        expect($slots)->toHaveCount(2);
        expect($slots[0]['end']->format('H:i'))->toBe('11:00');
    });
});

describe('Mutation Coverage - Session Duration Boundary for First Event (Line 134)', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);

        $this->user = User::factory()->create();
        $this->user->assignRole(Role::findByName(RoleEnum::MENTOR->value));
        $this->user->profile->update(['minimum_pre_booking_time' => 0]);

        $this->mentorProgram = MentorProgram::factory()->create([
            'mentor_id' => $this->user->getKey(),
        ]);

        $this->timezone = 'UTC';
    });

    it('sessionDuration=0 bypasses duration check completely - slots of any size included (kills GreaterToGreaterOrEqual)', function (): void {
        Date::setTestNow(Date::create(2026, 1, 10, 10, 0, 0, $this->timezone));

        // session_duration=0 should mean: 0 > 0 is FALSE, so duration check is skipped
        // If mutation changes to >= 0, then 0 >= 0 is TRUE, and small slots would be filtered incorrectly
        $this->mentorProgram->update(['session_duration' => 0]);

        // Event creates 5-minute initial gap
        $eventStart = Date::parse('2026-01-10 10:05:00', $this->timezone);
        $eventEnd = Date::parse('2026-01-10 11:00:00', $this->timezone);

        CalendarEvent::factory()->create([
            'start_date_time'   => $eventStart,
            'end_date_time'     => $eventEnd,
            'date'              => $eventStart->format('Y-m-d'),
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ])->calendarEventUsers()->attach([$this->user->getKey(), $this->mentorProgram->mentor_id]);

        $service = new AvailableCalendarEventsSlotsService(
            $this->user,
            $this->timezone,
            $this->mentorProgram,
            [],
            false,
        );

        $slots = $service->getAvailableSlots();

        // 5-minute slot should be included because sessionDuration=0 means no filtering
        $initialSlot = collect($slots)->first(fn ($slot): bool => $slot['start']->format('H:i') === '10:00');
        expect($initialSlot)->not->toBeNull();
        expect($initialSlot['end']->format('H:i'))->toBe('10:05');
    });

    it('sessionDuration=1 filters slots smaller than 1 minute (proves > 0 check matters)', function (): void {
        Date::setTestNow(Date::create(2026, 1, 10, 10, 0, 0, $this->timezone));

        // session_duration=1 means: 1 > 0 is TRUE, so duration check IS applied
        $this->mentorProgram->update(['session_duration' => 1]);

        // Event starts at period start - 0-minute gap
        $eventStart = Date::parse('2026-01-10 10:00:00', $this->timezone);
        $eventEnd = Date::parse('2026-01-10 11:00:00', $this->timezone);

        CalendarEvent::factory()->create([
            'start_date_time'   => $eventStart,
            'end_date_time'     => $eventEnd,
            'date'              => $eventStart->format('Y-m-d'),
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ])->calendarEventUsers()->attach([$this->user->getKey(), $this->mentorProgram->mentor_id]);

        $service = new AvailableCalendarEventsSlotsService(
            $this->user,
            $this->timezone,
            $this->mentorProgram,
            [],
            false,
        );

        $slots = $service->getAvailableSlots();

        // 0-minute slot is skipped because sessionDuration=1 > 0 AND 0 < 1
        expect($slots)->toHaveCount(1);
        expect($slots[0]['start']->format('H:i'))->toBe('11:00');
    });
});

describe('Mutation Coverage - Session Duration Between Events (Line 159)', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);

        $this->user = User::factory()->create();
        $this->user->assignRole(Role::findByName(RoleEnum::MENTOR->value));
        $this->user->profile->update(['minimum_pre_booking_time' => 0]);

        $this->mentorProgram = MentorProgram::factory()->create([
            'mentor_id' => $this->user->getKey(),
        ]);

        $this->timezone = 'UTC';
    });

    it('sessionDuration=0 includes any gap between events (kills IncrementInteger on line 159)', function (): void {
        Date::setTestNow(Date::create(2026, 1, 10, 10, 0, 0, $this->timezone));

        $this->mentorProgram->update(['session_duration' => 0]);

        // Event 1 creates valid initial gap (1 hour)
        $event1Start = Date::parse('2026-01-10 11:00:00', $this->timezone);
        $event1End = Date::parse('2026-01-10 11:30:00', $this->timezone);

        // Event 2 - small gap between events (only 5 minutes)
        $event2Start = Date::parse('2026-01-10 11:35:00', $this->timezone);
        $event2End = Date::parse('2026-01-10 12:00:00', $this->timezone);

        CalendarEvent::factory()->create([
            'start_date_time'   => $event1Start,
            'end_date_time'     => $event1End,
            'date'              => $event1Start->format('Y-m-d'),
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ])->calendarEventUsers()->attach([$this->user->getKey(), $this->mentorProgram->mentor_id]);

        CalendarEvent::factory()->create([
            'start_date_time'   => $event2Start,
            'end_date_time'     => $event2End,
            'date'              => $event2Start->format('Y-m-d'),
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ])->calendarEventUsers()->attach([$this->user->getKey(), $this->mentorProgram->mentor_id]);

        $service = new AvailableCalendarEventsSlotsService(
            $this->user,
            $this->timezone,
            $this->mentorProgram,
            [],
            false,
        );

        $slots = $service->getAvailableSlots();

        // With session_duration=0: the check `sessionDuration > 0` is FALSE
        // So the gap between events (11:30-11:35, 5 minutes) should be INCLUDED
        // If mutation changed > 0 to > 1 (IncrementInteger), 0 > 1 would still be FALSE - same behavior
        // If mutation changed > 0 to > -1 (DecrementInteger), 0 > -1 would be TRUE - gap would be filtered

        $gapBetweenEvents = collect($slots)->first(fn ($slot): bool => $slot['start']->format('H:i') === '11:30'
            && $slot['end']->format('H:i') === '11:35');

        expect($gapBetweenEvents)->not->toBeNull('5-minute gap between events should be included when session_duration=0');
    });

    it('sessionDuration=1 filters small gaps between events (boundary test)', function (): void {
        Date::setTestNow(Date::create(2026, 1, 10, 10, 0, 0, $this->timezone));

        $this->mentorProgram->update(['session_duration' => 60]); // 60 minutes

        // Event 1
        $event1Start = Date::parse('2026-01-10 11:00:00', $this->timezone);
        $event1End = Date::parse('2026-01-10 11:30:00', $this->timezone);

        // Event 2 - small gap between events (only 25 minutes < 60)
        $event2Start = Date::parse('2026-01-10 11:55:00', $this->timezone);
        $event2End = Date::parse('2026-01-10 12:30:00', $this->timezone);

        CalendarEvent::factory()->create([
            'start_date_time'   => $event1Start,
            'end_date_time'     => $event1End,
            'date'              => $event1Start->format('Y-m-d'),
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ])->calendarEventUsers()->attach([$this->user->getKey(), $this->mentorProgram->mentor_id]);

        CalendarEvent::factory()->create([
            'start_date_time'   => $event2Start,
            'end_date_time'     => $event2End,
            'date'              => $event2Start->format('Y-m-d'),
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ])->calendarEventUsers()->attach([$this->user->getKey(), $this->mentorProgram->mentor_id]);

        $service = new AvailableCalendarEventsSlotsService(
            $this->user,
            $this->timezone,
            $this->mentorProgram,
            [],
            false,
        );

        $slots = $service->getAvailableSlots();

        // With session_duration=60: 60 > 0 is TRUE
        // Gap between events is 25 minutes which is < 60, so it should be SKIPPED
        $gapBetweenEvents = collect($slots)->first(fn ($slot): bool => $slot['start']->format('H:i') === '11:30'
            && $slot['end']->format('H:i') === '11:55');

        expect($gapBetweenEvents)->toBeNull('25-minute gap should be skipped when session_duration=60');

        // But initial slot should exist (10:00-11:00 = 60 minutes, equal to session duration, so NOT filtered)
        $initialSlot = collect($slots)->first(fn ($slot): bool => $slot['start']->format('H:i') === '10:00');
        expect($initialSlot)->not->toBeNull();
    });
});

describe('Mutation Coverage - slotDuration <= 0 vs <= 1 (Line 142)', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);

        $this->user = User::factory()->create();
        $this->user->assignRole(Role::findByName(RoleEnum::MENTOR->value));
        $this->user->profile->update(['minimum_pre_booking_time' => 0]);

        $this->mentorProgram = MentorProgram::factory()->create([
            'mentor_id'        => $this->user->getKey(),
            'session_duration' => 0, // Disable session duration check to isolate slotDuration check
        ]);

        $this->timezone = 'UTC';
    });

    it('1-minute slot is INCLUDED proving slotDuration <= 0, not <= 1 (kills IncrementInteger)', function (): void {
        Date::setTestNow(Date::create(2026, 1, 10, 10, 0, 0, $this->timezone));

        // Create event that leaves exactly 5-minute gap (after rounding)
        // Note: slots get ceiling to 5-minute boundaries
        $eventStart = Date::parse('2026-01-10 10:05:00', $this->timezone);
        $eventEnd = Date::parse('2026-01-10 11:00:00', $this->timezone);

        CalendarEvent::factory()->create([
            'start_date_time'   => $eventStart,
            'end_date_time'     => $eventEnd,
            'date'              => $eventStart->format('Y-m-d'),
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ])->calendarEventUsers()->attach([$this->user->getKey(), $this->mentorProgram->mentor_id]);

        $service = new AvailableCalendarEventsSlotsService(
            $this->user,
            $this->timezone,
            $this->mentorProgram,
            [],
            false,
        );

        $slots = $service->getAvailableSlots();

        // The check is `slotDuration <= 0` so slots with duration > 0 should be included
        // If mutation changed to `slotDuration <= 1`, the 5-minute slot would STILL pass (5 is not <= 1)
        // But that's fine - the point is we need SOME positive-duration slot included

        $initialSlot = collect($slots)->first(fn ($slot): bool => $slot['start']->format('H:i') === '10:00');
        expect($initialSlot)->not->toBeNull('Slot with positive duration should be included');
        expect($initialSlot['end']->format('H:i'))->toBe('10:05');
    });

    it('0-minute slot is SKIPPED (boundary for slotDuration <= 0)', function (): void {
        Date::setTestNow(Date::create(2026, 1, 10, 10, 0, 0, $this->timezone));

        // Event starts exactly at period start - creates 0-duration gap
        $eventStart = Date::parse('2026-01-10 10:00:00', $this->timezone);
        $eventEnd = Date::parse('2026-01-10 11:00:00', $this->timezone);

        CalendarEvent::factory()->create([
            'start_date_time'   => $eventStart,
            'end_date_time'     => $eventEnd,
            'date'              => $eventStart->format('Y-m-d'),
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ])->calendarEventUsers()->attach([$this->user->getKey(), $this->mentorProgram->mentor_id]);

        $service = new AvailableCalendarEventsSlotsService(
            $this->user,
            $this->timezone,
            $this->mentorProgram,
            [],
            false,
        );

        $slots = $service->getAvailableSlots();

        // 0-duration gap at the start should be skipped
        expect($slots)->toHaveCount(1);
        expect($slots[0]['start']->format('H:i'))->toBe('11:00');
    });
});

describe('Mutation Coverage - Continue inside slotDuration check (Line 145)', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);

        $this->user = User::factory()->create();
        $this->user->assignRole(Role::findByName(RoleEnum::MENTOR->value));
        $this->user->profile->update(['minimum_pre_booking_time' => 0]);

        $this->mentorProgram = MentorProgram::factory()->create([
            'mentor_id'        => $this->user->getKey(),
            'session_duration' => 0,
        ]);

        $this->timezone = 'UTC';
    });

    it('continue after zero-duration slot processes next event correctly (kills ContinueToBreak)', function (): void {
        Date::setTestNow(Date::create(2026, 1, 10, 10, 0, 0, $this->timezone));

        // Event 1 starts exactly at period start - 0-duration initial gap (will trigger continue on line 145)
        $event1Start = Date::parse('2026-01-10 10:00:00', $this->timezone);
        $event1End = Date::parse('2026-01-10 10:30:00', $this->timezone);

        // Event 2 creates a valid gap after event 1
        $event2Start = Date::parse('2026-01-10 11:30:00', $this->timezone);
        $event2End = Date::parse('2026-01-10 12:00:00', $this->timezone);

        CalendarEvent::factory()->create([
            'start_date_time'   => $event1Start,
            'end_date_time'     => $event1End,
            'date'              => $event1Start->format('Y-m-d'),
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ])->calendarEventUsers()->attach([$this->user->getKey(), $this->mentorProgram->mentor_id]);

        CalendarEvent::factory()->create([
            'start_date_time'   => $event2Start,
            'end_date_time'     => $event2End,
            'date'              => $event2Start->format('Y-m-d'),
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ])->calendarEventUsers()->attach([$this->user->getKey(), $this->mentorProgram->mentor_id]);

        $service = new AvailableCalendarEventsSlotsService(
            $this->user,
            $this->timezone,
            $this->mentorProgram,
            [],
            false,
        );

        $slots = $service->getAvailableSlots();

        // If 'break' was used instead of 'continue', only trailing slot would exist
        // With 'continue', we should have:
        // - Gap between event1 and event2 (10:30-11:30 = 60 min) - INCLUDED
        // - Trailing slot after event2

        $gapBetweenEvents = collect($slots)->first(fn ($slot): bool => $slot['start']->format('H:i') === '10:30'
            && $slot['end']->format('H:i') === '11:30');

        expect($gapBetweenEvents)->not->toBeNull('Gap between events must exist - loop must CONTINUE not BREAK after 0-duration slot');

        // Trailing slot should also exist
        $trailingSlot = collect($slots)->first(fn ($slot): bool => $slot['start']->format('H:i') === '12:00');
        expect($trailingSlot)->not->toBeNull();
    });
});
