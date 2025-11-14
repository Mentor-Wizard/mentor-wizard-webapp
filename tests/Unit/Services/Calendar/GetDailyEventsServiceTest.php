<?php

declare(strict_types=1);

use App\Models\CalendarEvent;
use App\Models\User;
use App\Services\Calendar\GetDailyCalendarEventsService;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Date;

mutates(GetDailyCalendarEventsService::class);

describe('GetDailyCalendarEventsService Service', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
    });

    it('builds daily calendar grouped by month and appends days, marking flags correctly via service', function (): void {
        Date::setTestNow(Date::create(2025, 3, 5, 8, 0, 0));
        $tz = 'UTC';

        /** @var User $user */
        $user = User::factory()->create();

        // Create an event on the selected day so hasEvent can be asserted
        $start = Date::create(2025, 3, 5, 14, 0, 0);
        $end = (clone $start)->addMinutes(90);

        /** @var CalendarEvent $event */
        $event = CalendarEvent::query()->create([
            'title'           => 'Daily CalendarEvent',
            'status'          => 'confirmed',
            'start_date_time' => $start,
            'end_date_time'   => $end,
            'duration'        => $end->diffInSeconds($start),
            'date'            => $start->format('Y-m-d'),
            'type'            => 'group',
        ]);

        $user->calendarEvents()->attach($event->getKey());
        $checkedDate = Date::parse('2025-03-05');
        $result = new GetDailyCalendarEventsService($user, $checkedDate, $tz)->getDailyCalendarEvents();

        expect($result)->toHaveKeys(['events', 'calendarView']);

        $ym = '2025-03';
        $calendar = $result['calendarView'];

        expect($calendar)->toHaveKey($ym);
        $days = $calendar[$ym];

        // Ensure multiple days present to cover both set and append branches
        expect($days)->toBeArray()->and(count($days))->toBeGreaterThan(10);

        $selected = collect($days)->firstWhere('date', '2025-03-05');
        expect($selected)
            ->toBeArray()
            ->and($selected['isCurrentMonth'] ?? null)->toBeTrue()
            ->and($selected['isSelected'] ?? null)->toBeTrue()
            ->and($selected['isToday'] ?? null)->toBeTrue()
            ->and($selected['hasEvent'] ?? null)->toBeTrue();
    });

    it('sets event date property using timezone via each() method', function (): void {
        Date::setTestNow(Date::create(2025, 3, 5, 23, 0, 0));
        $tz = 'Pacific/Auckland';

        /** @var User $user */
        $user = User::factory()->create();

        // Create event at 23:00 UTC which should be next day in Auckland
        $start = Date::create(2025, 3, 5, 23, 0, 0);
        $end = (clone $start)->addHour();

        /** @var CalendarEvent $event */
        $event = CalendarEvent::query()->create([
            'title'           => 'Late Event',
            'status'          => 'confirmed',
            'start_date_time' => $start,
            'end_date_time'   => $end,
            'duration'        => $end->diffInSeconds($start),
            'date'            => $start->format('Y-m-d'),
            'type'            => 'individual',
        ]);

        $user->calendarEvents()->attach($event->getKey());
        $checkedDate = Date::parse('2025-03-05');
        $result = new GetDailyCalendarEventsService($user, $checkedDate, $tz)->getDailyCalendarEvents();

        // Verify calendarView has proper structure with hasEvent flags
        $calendar = $result['calendarView'];
        expect($calendar)->toBeArray();

        // Find a day with hasEvent flag to verify each() worked
        $hasEventDay = collect($calendar)->flatten(1)->firstWhere('hasEvent', true);
        expect($hasEventDay)->not->toBeNull()
            ->and($hasEventDay['hasEvent'])->toBeTrue();
    });

    it('initializes calendar months correctly with both set and append operations', function (): void {
        Date::setTestNow(Date::create(2025, 3, 5, 12, 0, 0));
        $tz = 'UTC';

        /** @var User $user */
        $user = User::factory()->create();

        // Create events in different months to trigger multiple month processing
        $event1Start = Date::create(2024, 12, 15, 10, 0, 0);
        $event1End = (clone $event1Start)->addHour();

        $event1 = CalendarEvent::query()->create([
            'title'           => 'Old Event',
            'status'          => 'confirmed',
            'start_date_time' => $event1Start,
            'end_date_time'   => $event1End,
            'duration'        => $event1End->diffInSeconds($event1Start),
            'date'            => $event1Start->format('Y-m-d'),
            'type'            => 'individual',
        ]);

        $event2Start = Date::create(2025, 6, 20, 14, 0, 0);
        $event2End = (clone $event2Start)->addHour();

        $event2 = CalendarEvent::query()->create([
            'title'           => 'Future Event',
            'status'          => 'confirmed',
            'start_date_time' => $event2Start,
            'end_date_time'   => $event2End,
            'duration'        => $event2End->diffInSeconds($event2Start),
            'date'            => $event2Start->format('Y-m-d'),
            'type'            => 'individual',
        ]);

        $user->calendarEvents()->attach([$event1->getKey(), $event2->getKey()]);
        $checkedDate = Date::parse('2025-03-05');
        $result = new GetDailyCalendarEventsService($user, $checkedDate, $tz)->getDailyCalendarEvents();

        $calendar = $result['calendarView'];

        // Should have multiple month keys (including 2024-12, 2025-01, ..., 2025-06)
        expect($calendar)->toBeArray()
            ->and(count($calendar))->toBeGreaterThan(3);

        // Each month should have an array of days
        foreach ($calendar as $days) {
            expect($days)->toBeArray()->not->toBeEmpty();
        }
    });
});
