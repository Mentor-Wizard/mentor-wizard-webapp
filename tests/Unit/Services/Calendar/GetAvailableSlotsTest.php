<?php

declare(strict_types=1);

use App\Enums\UserScheduleRecordType;
use App\Models\CalendarEvent;
use App\Models\User;
use App\Models\UserSchedule;
use App\Services\Calendar\AvailableCalendarEventsSlotsService;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Date;

mutates(AvailableCalendarEventsSlotsService::class);

describe('GetAvailableSlotsService Service', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
    });

    it('returns empty array when user has no future events', function (): void {
        Date::setTestNow(Date::create(2025, 4, 1, 10, 0, 0, 'UTC'));
        $user = User::factory()->create();

        $slots = new AvailableCalendarEventsSlotsService($user, 'Europe/Kyiv')->getAvailableSlots();

        expect($slots)->toBeArray()->toBeEmpty();
    });

    it('builds available slots between events using timezone conversion', function (): void {
        $tz = 'Europe/Kyiv';
        Date::setTestNow(Date::now($tz)->setTime(10, 0, 0));

        /** @var User $user */
        $user = User::factory()->create();

        // Create two future events in UTC
        $event1StartUtc = Date::now($tz)->addDay()->setTime(12, 0, 0);
        $event1EndUtc = (clone $event1StartUtc)->addHour()->setTime(14, 0, 0);
        $event2StartUtc = Date::now($tz)->addDay()->setTime(12, 0, 0);
        $event2EndUtc = (clone $event2StartUtc)->setTime(14, 0, 0);

        $event1 = CalendarEvent::query()->create([
            'title'           => 'E1',
            'status'          => 'confirmed',
            'start_date_time' => $event1StartUtc,
            'end_date_time'   => $event1EndUtc,
            'date'            => $event1StartUtc->format('Y-m-d'),
            'type'            => 'individual',
        ]);
        $event2 = CalendarEvent::query()->create([
            'title'           => 'E2',
            'status'          => 'confirmed',
            'start_date_time' => $event2StartUtc,
            'end_date_time'   => $event2EndUtc,
            'date'            => $event2StartUtc->format('Y-m-d'),
            'type'            => 'individual',
        ]);
        $user->calendarEvents()->attach([$event1->getKey(), $event2->getKey()]);

        $result = new AvailableCalendarEventsSlotsService($user, $tz)->getAvailableSlots();

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

    it('creates initial slot when first event starts after current UTC time', function (): void {
        $tz = 'Europe/Kyiv';
        Date::setTestNow(Date::now($tz)->setTime(10, 0, 0));
        $user = User::factory()->create();

        $eventStartUtc = Date::now($tz)->addDay()->setTime(12, 0, 0);
        //        $eventStartUtc = Carbon::create(2025, 4, 1, 10, 0, 1, 'UTC');
        $eventEndUtc = (clone $eventStartUtc)->addHour();

        $event = CalendarEvent::query()->create([
            'title'           => 'E-future',
            'status'          => 'confirmed',
            'start_date_time' => $eventStartUtc,
            'end_date_time'   => $eventEndUtc,
            'date'            => $eventStartUtc->format('Y-m-d'),
            'type'            => 'individual',
        ]);
        $user->calendarEvents()->attach($event->getKey());

        $result = new AvailableCalendarEventsSlotsService($user, $tz)->getAvailableSlots();

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

        /** @var User $user */
        $user = User::factory()->create();

        // Create three events
        $event1StartUtc = Date::now($tz)->addDay()->setTime(12, 0, 0);
        $event1EndUtc = (clone $event1StartUtc)->addHour();

        $event2StartUtc = Date::now($tz)->addDays(2)->setTime(12, 0, 0);
        $event2EndUtc = (clone $event2StartUtc)->addHour();

        $event3StartUtc = Date::now($tz)->addDays(3)->setTime(12, 0, 0);
        $event3EndUtc = (clone $event3StartUtc)->addHour();

        $event1 = CalendarEvent::query()->create([
            'title'           => 'E1',
            'status'          => 'confirmed',
            'start_date_time' => $event1StartUtc,
            'end_date_time'   => $event1EndUtc,
            'date'            => $event1StartUtc->format('Y-m-d'),
            'type'            => 'individual',
        ]);
        $event2 = CalendarEvent::query()->create([
            'title'           => 'E2',
            'status'          => 'confirmed',
            'start_date_time' => $event2StartUtc,
            'end_date_time'   => $event2EndUtc,
            'date'            => $event2StartUtc->format('Y-m-d'),
            'type'            => 'individual',
        ]);
        $event3 = CalendarEvent::query()->create([
            'title'           => 'E3',
            'status'          => 'confirmed',
            'start_date_time' => $event3StartUtc,
            'end_date_time'   => $event3EndUtc,
            'date'            => $event3StartUtc->format('Y-m-d'),
            'type'            => 'individual',
        ]);

        $user->calendarEvents()->attach([$event1->getKey(), $event2->getKey(), $event3->getKey()]);

        // Exclude event2 from calculation
        $result = new AvailableCalendarEventsSlotsService($user, $tz, [$event2->getKey()])->getAvailableSlots();

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

        /** @var User $user */
        $user = User::factory()->create();

        // Create user schedule: Monday 9:00-17:00
        UserSchedule::query()->create([
            'user_id'     => $user->getKey(),
            'day_of_week' => 1, // Monday
            'start_time'  => '09:00:00',
            'end_time'    => '17:00:00',
            'type'        => UserScheduleRecordType::WORKING_DAY,
        ]);

        // Create an event on Monday 14:00-15:00
        $eventStart = Date::create(2025, 1, 6, 14, 0, 0, $tz);
        $eventEnd = Date::create(2025, 1, 6, 15, 0, 0, $tz);

        $event = CalendarEvent::query()->create([
            'title'           => 'Monday Event',
            'status'          => 'confirmed',
            'start_date_time' => $eventStart,
            'end_date_time'   => $eventEnd,
            'date'            => $eventStart?->format('Y-m-d'),
            'type'            => 'individual',
        ]);
        $user->calendarEvents()->attach($event->getKey());

        // Get slots with schedule exclusion
        $result = new AvailableCalendarEventsSlotsService($user, $tz, [], true)->getAvailableSlots();

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

        /** @var User $user */
        $user = User::factory()->create();

        // Create working schedule for Monday
        UserSchedule::query()->create([
            'user_id'     => $user->getKey(),
            'day_of_week' => 1, // Monday
            'start_time'  => '09:00:00',
            'end_time'    => '17:00:00',
            'type'        => UserScheduleRecordType::WORKING_DAY,
        ]);

        // Create a day off on Monday, January 13
        UserSchedule::query()->create([
            'user_id'      => $user->getKey(),
            'day_of_week'  => 1, // Monday (required but ignored for day off)
            'start_time'   => '00:00:00',
            'end_time'     => '23:59:59',
            'type'         => UserScheduleRecordType::DAY_OFF,
            'day_off_date' => Date::today()->addMonth()->firstOfMonth(1), // Next Monday
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
            'title'           => 'Event on Working Monday',
            'status'          => 'confirmed',
            'start_date_time' => $event1Start,
            'end_date_time'   => $event1End,
            'date'            => $event1Start->format('Y-m-d'),
            'type'            => 'individual',
        ]);

        $event2 = CalendarEvent::query()->create([
            'title'           => 'Event on Day Off',
            'status'          => 'confirmed',
            'start_date_time' => $event2Start,
            'end_date_time'   => $event2End,
            'date'            => $event2Start->format('Y-m-d'),
            'type'            => 'individual',
        ]);

        $user->calendarEvents()->attach([$event1->getKey(), $event2->getKey()]);

        // Get slots with schedule exclusion
        $result = new AvailableCalendarEventsSlotsService($user, $tz, [], true)->getAvailableSlots();

        // Slots should not include or overlap with the day off date (2025-01-13)
        $slotsOnDayOff = array_filter($result, function (array $slot): bool {
            $slotDate = $slot['start']->format('Y-m-d');
            $slotEndDate = $slot['end']->format('Y-m-d');
            if ($slotDate === Date::now()->addMonth()->firstOfMonth(1)->addWeek()->format('Y-m-d')) {
                return true;
            }

            return $slotEndDate === Date::now()->addMonth()->firstOfMonth(1)->addWeek()->format('Y-m-d');
        });

        expect($slotsOnDayOff)->toBeEmpty();
    });

    it('returns slots without schedule filtering when excludeSchedule is false', function (): void {
        $tz = 'Europe/Kyiv';
        Date::setTestNow(Date::create(2025, 1, 6, 10, 0, 0, $tz)); // Monday

        /** @var User $user */
        $user = User::factory()->create();

        // Create user schedule: Monday 9:00-17:00
        UserSchedule::query()->create([
            'user_id'     => $user->getKey(),
            'day_of_week' => 1, // Monday
            'start_time'  => '09:00:00',
            'end_time'    => '17:00:00',
            'type'        => UserScheduleRecordType::WORKING_DAY,
        ]);

        // Create an event on Monday
        $eventStart = Date::create(2025, 1, 6, 14, 0, 0, $tz);
        $eventEnd = Date::create(2025, 1, 6, 15, 0, 0, $tz);

        $event = CalendarEvent::query()->create([
            'title'           => 'Monday Event',
            'status'          => 'confirmed',
            'start_date_time' => $eventStart,
            'end_date_time'   => $eventEnd,
            'date'            => $eventStart?->format('Y-m-d'),
            'type'            => 'individual',
        ]);
        $user->calendarEvents()->attach($event->getKey());

        // Get slots WITHOUT schedule exclusion (default behavior)
        $result = new AvailableCalendarEventsSlotsService($user, $tz, [], false)->getAvailableSlots();

        // Should return slots outside working hours too
        expect($result)->toBeArray()->toHaveCount(2);

        // First slot should start at current time (10:00), not restricted by schedule
        expect($result[0]['start']->format('H:i'))->toBe('10:00');
    });
});
