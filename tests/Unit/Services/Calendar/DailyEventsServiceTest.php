<?php

declare(strict_types=1);

use App\Models\CalendarEvent;
use App\Models\User;
use App\Services\Calendar\DailyCalendarEventsService;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Date;

mutates(DailyCalendarEventsService::class);

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
            'date'            => $start?->format('Y-m-d'),
            'type'            => 'group',
        ]);

        $user->calendarEvents()->attach($event->getKey());
        $checkedDate = Date::parse('2025-03-05');
        $result = new DailyCalendarEventsService($user, $checkedDate, $tz)->getDailyCalendarEvents();

        expect($result)->toHaveKeys(['calendarEvents', 'calendarView']);

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
            'date'            => $start?->format('Y-m-d'),
            'type'            => 'individual',
        ]);

        $user->calendarEvents()->attach($event->getKey());
        $checkedDate = Date::parse('2025-03-05');
        $result = new DailyCalendarEventsService($user, $checkedDate, $tz)->getDailyCalendarEvents();

        // Verify calendarView has proper structure with hasEvent flags
        $calendar = $result['calendarView'];
        expect($calendar)->toBeArray();

        // Find a day with hasEvent flag to verify each() worked
        $hasEventDay = collect($calendar)->flatten(1)->firstWhere('hasEvent', true);
        expect($hasEventDay)->not->toBeNull()
            ->and($hasEventDay['hasEvent'])->toBeTrue();
    });
});
