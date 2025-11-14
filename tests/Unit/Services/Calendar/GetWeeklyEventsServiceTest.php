<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\Calendar\GetWeeklyCalendarEventsService;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Date;

mutates(GetWeeklyCalendarEventsService::class);

describe('GetWeeklyCalendarEventsService Service', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
    });

    it('sets flags in week formatted calendar (isCurrentMonth, isSelected, isToday) using service', function (): void {
        // Freeze time to ensure deterministic behaviour
        Date::setTestNow(Date::create(2025, 1, 15, 12, 0, 0));
        $tz = config('app.timezone');

        /** @var User $user */
        $user = User::factory()->create();

        $date = '2025-01-15'; // Wednesday
        $checkedDate = Date::parse($date);
        $result = new GetWeeklyCalendarEventsService($user, $checkedDate, $tz)->getWeeklyCalendarEvents();

        expect($result)
            ->toHaveKeys(['calendarEvents', 'calendarView']);

        $entryForSelected = collect($result['calendarView'])
            ->firstWhere('date', $date);

        expect($entryForSelected)
            ->toBeArray()
            ->and($entryForSelected['isCurrentMonth'] ?? null)->toBeTrue()
            ->and($entryForSelected['isSelected'] ?? null)->toBeTrue()
            ->and($entryForSelected['isToday'] ?? null)->toBeTrue();
    });

    it('sets event date property using timezone via each() method and only marks correct dates with hasEvent', function (): void {
        Date::setTestNow(Date::create(2025, 1, 15, 12, 0, 0));
        // Use timezone that shifts the date
        $tz = 'America/Los_Angeles'; // UTC-8

        /** @var User $user */
        $user = User::factory()->create();

        // Create event at 01:00 UTC on Jan 14
        // In LA timezone (UTC-8), this is 17:00 (5pm) on Jan 13
        $start = Date::create(2025, 1, 14, 1, 0, 0);
        $end = (clone $start)->addHour();

        $event = App\Models\CalendarEvent::query()->create([
            'title'           => 'Early Morning UTC Event',
            'status'          => 'confirmed',
            'start_date_time' => $start,
            'end_date_time'   => $end,
            'duration'        => $end->diffInSeconds($start),
            'date'            => $start->format('Y-m-d'), // This is 2025-01-14 in UTC
            'type'            => 'individual',
        ]);

        $user->calendarEvents()->attach($event->getKey());
        $checkedDate = Date::parse('2025-01-15');
        $result = new GetWeeklyCalendarEventsService($user, $checkedDate, $tz)->getWeeklyCalendarEvents();

        $calendar = $result['calendarView'];

        // Without each() setting the date property with timezone,
        // hasEvent would be on wrong date (2025-01-14 instead of 2025-01-13)
        $hasEventDays = collect($calendar)->filter(fn ($day): bool => isset($day['hasEvent']) && $day['hasEvent'])->pluck('date')->toArray();

        // Should be on Jan 13 in LA timezone, not Jan 14
        expect($hasEventDays)->toBe(['2025-01-13']);

        // Verify Jan 14 does NOT have hasEvent
        $jan14 = collect($calendar)->firstWhere('date', '2025-01-14');
        expect($jan14['hasEvent'] ?? false)->toBeFalse();
    });

    it('returns calendarView with sequential numeric keys via array_values', function (): void {
        Date::setTestNow(Date::create(2025, 1, 15, 12, 0, 0));
        $tz = config('app.timezone');

        /** @var User $user */
        $user = User::factory()->create();
        $checkedDate = Date::parse('2025-01-15');
        $result = new GetWeeklyCalendarEventsService($user, $checkedDate, $tz)->getWeeklyCalendarEvents();

        $calendarView = $result['calendarView'];

        // Verify array has sequential numeric keys (0, 1, 2, ...)
        expect($calendarView)->toBeArray()
            ->and(array_keys($calendarView))->toBe([0, 1, 2, 3, 4, 5, 6])
            ->and(count($calendarView))->toBe(7);

        // Each entry should be an array with date key
        foreach ($calendarView as $day) {
            expect($day)->toBeArray()
                ->and($day)->toHaveKey('date');
        }
    });
});
