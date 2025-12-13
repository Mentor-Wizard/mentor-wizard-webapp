<?php

declare(strict_types=1);

use App\Models\CalendarEvent;
use App\Models\User;
use App\Services\Calendar\CheckTimeSlotReservedService;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Date;

mutates(CheckTimeSlotReservedService::class);

describe('CheckTimeSlotReservedService Service', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
    });

    it('returns true when user has no future events (no conflicts)', function (): void {
        Date::setTestNow(Date::create(2025, 4, 1, 10, 0, 0));
        $user = User::factory()->create();

        $service = new CheckTimeSlotReservedService(
            fromDate: '2025-04-02',
            fromTime: '14:00',
            toDate: '2025-04-02',
            toTime: '15:00',
            timezone: 'Europe/Kyiv',
            user: $user
        );

        expect($service->isSlotAvailable())->toBeTrue();
    });

    it('returns true when requested interval fits entirely within an available slot', function (): void {
        Date::setTestNow(Date::create(2025, 4, 1, 10, 0, 0));
        $tz = 'Europe/Kyiv';
        $user = User::factory()->create();

        // Create event from 12:00-13:00 UTC (15:00-16:00 Kyiv)
        $event1StartUtc = Date::create(2025, 4, 1, 12, 0, 0);
        $event1EndUtc = (clone $event1StartUtc)->addHour();

        $event1 = CalendarEvent::query()->create([
            'title'           => 'E1',
            'status'          => 'confirmed',
            'start_date_time' => $event1StartUtc,
            'end_date_time'   => $event1EndUtc,
            'date'            => $event1StartUtc?->format('Y-m-d'),
            'type'            => 'individual',
        ]);
        $user->calendarEvents()->attach($event1->getKey());

        // Request slot before the event (13:00-14:00 Kyiv)
        $service = new CheckTimeSlotReservedService(
            fromDate: '2025-04-01',
            fromTime: '13:00',
            toDate: '2025-04-01',
            toTime: '14:00',
            timezone: $tz,
            user: $user
        );

        expect($service->isSlotAvailable())->toBeTrue();
    });

    it('returns false when requested interval overlaps with existing event', function (): void {
        Date::setTestNow(Date::create(2025, 4, 1, 10, 0, 0));
        $tz = 'Europe/Kyiv';
        $user = User::factory()->create();

        // Create event from 12:00-13:00 UTC (15:00-16:00 Kyiv)
        $event1StartUtc = Date::create(2025, 4, 1, 12, 0, 0);
        $event1EndUtc = (clone $event1StartUtc)->addHour();

        $event1 = CalendarEvent::query()->create([
            'title'           => 'E1',
            'status'          => 'confirmed',
            'start_date_time' => $event1StartUtc,
            'end_date_time'   => $event1EndUtc,
            'date'            => $event1StartUtc?->format('Y-m-d'),
            'type'            => 'individual',
        ]);
        $user->calendarEvents()->attach($event1->getKey());

        // Request slot that overlaps the event (14:30-15:30 Kyiv)
        $service = new CheckTimeSlotReservedService(
            fromDate: '2025-04-01',
            fromTime: '14:30',
            toDate: '2025-04-01',
            toTime: '15:30',
            timezone: $tz,
            user: $user
        );

        expect($service->isSlotAvailable())->toBeFalse();
    });

    it('returns true when requested slot is between two events', function (): void {
        Date::setTestNow(Date::create(2025, 4, 1, 10, 0, 0));
        $tz = 'Europe/Kyiv';
        $user = User::factory()->create();

        // Create two events
        $event1StartUtc = Date::create(2025, 4, 1, 12, 0, 0);
        $event1EndUtc = (clone $event1StartUtc)->addHour();
        $event2StartUtc = Date::create(2025, 4, 1, 15, 0, 0);
        $event2EndUtc = (clone $event2StartUtc)->addHour();

        $event1 = CalendarEvent::query()->create([
            'title'           => 'E1',
            'status'          => 'confirmed',
            'start_date_time' => $event1StartUtc,
            'end_date_time'   => $event1EndUtc,
            'date'            => $event1StartUtc?->format('Y-m-d'),
            'type'            => 'individual',
        ]);
        $event2 = CalendarEvent::query()->create([
            'title'           => 'E2',
            'status'          => 'confirmed',
            'start_date_time' => $event2StartUtc,
            'end_date_time'   => $event2EndUtc,
            'date'            => $event2StartUtc?->format('Y-m-d'),
            'type'            => 'individual',
        ]);
        $user->calendarEvents()->attach([$event1->getKey(), $event2->getKey()]);

        // Request slot between events (16:00-17:00 Kyiv, which is between 13:00-15:00 UTC events)
        $service = new CheckTimeSlotReservedService(
            fromDate: '2025-04-01',
            fromTime: '16:00',
            toDate: '2025-04-01',
            toTime: '17:00',
            timezone: $tz,
            user: $user
        );

        expect($service->isSlotAvailable())->toBeTrue();
    });

    it('excludes specified events when checking availability', function (): void {
        Date::setTestNow(Date::create(2025, 4, 1, 10, 0, 0));
        $tz = 'Europe/Kyiv';
        $user = User::factory()->create();

        // Create event from 12:00-13:00 UTC (15:00-16:00 Kyiv)
        $event1StartUtc = Date::create(2025, 4, 1, 12, 0, 0);
        $event1EndUtc = (clone $event1StartUtc)->addHour();

        $event1 = CalendarEvent::query()->create([
            'title'           => 'E1',
            'status'          => 'confirmed',
            'start_date_time' => $event1StartUtc,
            'end_date_time'   => $event1EndUtc,
            'date'            => $event1StartUtc?->format('Y-m-d'),
            'type'            => 'individual',
        ]);
        $user->calendarEvents()->attach($event1->getKey());

        // Request the exact slot of the event, but exclude that event
        $service = new CheckTimeSlotReservedService(
            fromDate: '2025-04-01',
            fromTime: '15:00',
            toDate: '2025-04-01',
            toTime: '16:00',
            timezone: $tz,
            user: $user,
            excludeEvents: [$event1->getKey()]
        );

        expect($service->isSlotAvailable())->toBeTrue();
    });
});
