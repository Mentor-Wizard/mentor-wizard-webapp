<?php

declare(strict_types=1);

use App\Models\CalendarEvent;
use App\Models\User;
use App\Services\Calendar\GetAvailableSlotsService;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Carbon;

mutates(GetAvailableSlotsService::class);

describe('GetAvailableSlotsService Service', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
    });

    it('returns empty array when user has no future events', function (): void {
        Illuminate\Support\Facades\Date::setTestNow(Illuminate\Support\Facades\Date::create(2025, 4, 1, 10, 0, 0, 'UTC'));
        $user = User::factory()->create();

        $slots = new GetAvailableSlotsService($user, 'Europe/Kyiv')->execute();

        expect($slots)->toBeArray()->toBeEmpty();
    });

    it('builds available slots between events using timezone conversion', function (): void {
        $tz = 'Europe/Kyiv';
        Illuminate\Support\Facades\Date::setTestNow(Illuminate\Support\Facades\Date::now($tz)->setTime(10, 0, 0));

        /** @var User $user */
        $user = User::factory()->create();

        // Create two future events in UTC
        $event1StartUtc = Illuminate\Support\Facades\Date::now($tz)->addDay()->setTime(12, 0, 0);
        $event1EndUtc = (clone $event1StartUtc)->addHour()->setTime(14, 0, 0);
        $event2StartUtc = Illuminate\Support\Facades\Date::now($tz)->addDay()->setTime(12, 0, 0);
        $event2EndUtc = (clone $event2StartUtc)->setTime(14, 0, 0);

        $event1 = CalendarEvent::query()->create([
            'title'           => 'E1',
            'status'          => 'confirmed',
            'start_date_time' => $event1StartUtc,
            'end_date_time'   => $event1EndUtc,
            'duration'        => $event1StartUtc->diffInSeconds($event1EndUtc),
            'date'            => $event1StartUtc->format('Y-m-d'),
            'type'            => 'individual',
        ]);
        $event2 = CalendarEvent::query()->create([
            'title'           => 'E2',
            'status'          => 'confirmed',
            'start_date_time' => $event2StartUtc,
            'end_date_time'   => $event2EndUtc,
            'duration'        => $event2StartUtc->diffInSeconds($event2EndUtc),
            'date'            => $event2StartUtc->format('Y-m-d'),
            'type'            => 'individual',
        ]);
        $user->calendarEvents()->attach([$event1->getKey(), $event2->getKey()]);

        $result = new GetAvailableSlotsService($user, $tz)->execute();

        // We expect 3 slots: [now..E1.start], [E1.end..E2.start], [E2.end..now+2months]
        expect($result)->toBeArray()->toHaveCount(3);

        // Slot 1 start is now in tz; end is E1 start in tz
        $slot1 = $result[0];

        expect($slot1['start']->equalTo(Illuminate\Support\Facades\Date::now($tz)
            ->setTime(10, 0, 0)->setTimezone($tz)))->toBeTrue()
            ->and($slot1['end']->equalTo($event1StartUtc->clone()->setTimezone($tz)))->toBeTrue();

        // Slot 2 between E1 end and E2 start in tz
        $slot2 = $result[1];
        expect($slot2['start']->equalTo($event1EndUtc->clone()->setTimezone($tz)))->toBeTrue()
            ->and($slot2['end']->equalTo($event2StartUtc->clone()->setTimezone($tz)))->toBeTrue();

        // Slot 3 ends at now+2 months in tz
        $slot3 = $result[2];
        expect($slot3['start']->equalTo($event2EndUtc->clone()->setTimezone($tz)))->toBeTrue()
            ->and($slot3['end']->equalTo(Illuminate\Support\Facades\Date::now($tz)->addMonths(CalendarEvent::MAXIMUM_NUMBER_OF_MONTHS_EVENT_CAN_BE_SET)
                ->setTime(10, 0, 0)))->toBeTrue();
    });

    it('creates initial slot when first event starts after current UTC time', function (): void {
        $tz = 'Europe/Kyiv';
        Illuminate\Support\Facades\Date::setTestNow(Illuminate\Support\Facades\Date::now($tz)->setTime(10, 0, 0));
        $user = User::factory()->create();

        $eventStartUtc = Illuminate\Support\Facades\Date::now($tz)->addDay()->setTime(12, 0, 0);
        //        $eventStartUtc = Carbon::create(2025, 4, 1, 10, 0, 1, 'UTC');
        $eventEndUtc = (clone $eventStartUtc)->addHour();

        $event = CalendarEvent::query()->create([
            'title'           => 'E-future',
            'status'          => 'confirmed',
            'start_date_time' => $eventStartUtc,
            'end_date_time'   => $eventEndUtc,
            'duration'        => $eventEndUtc->diffInSeconds($eventStartUtc),
            'date'            => $eventStartUtc->format('Y-m-d'),
            'type'            => 'individual',
        ]);
        $user->calendarEvents()->attach($event->getKey());

        $result = new GetAvailableSlotsService($user, $tz)->execute();

        // Two slots should exist: [now..E-future.start], [E-future.end..now+2months]
        expect($result)->toBeArray()->toHaveCount(2);

        expect($result[0]['start']
            ->equalTo(Illuminate\Support\Facades\Date::now($tz)->setTime(10, 0, 0)))->toBeTrue()
            ->and($result[0]['end']
                ->equalTo($eventStartUtc->clone()->setTimezone($tz)))->toBeTrue();

        expect($result[1]['start']
            ->equalTo($eventEndUtc->clone()->setTimezone($tz)))->toBeTrue()
            ->and($result[1]['end']
                ->equalTo(Illuminate\Support\Facades\Date::now($tz)
                    ->addMonths(CalendarEvent::MAXIMUM_NUMBER_OF_MONTHS_EVENT_CAN_BE_SET)))->toBeTrue();
    });
});
