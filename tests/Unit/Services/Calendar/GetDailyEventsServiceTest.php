<?php

declare(strict_types=1);

use App\Models\CalendarEvent;
use App\Models\User;
use App\Services\Calendar\GetDailyEventsService;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Carbon;

mutates(GetDailyEventsService::class);

describe('GetDailyEventsService Service', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
    });

    it('builds daily calendar grouped by month and appends days, marking flags correctly via service', function (): void {
        Carbon::setTestNow(Carbon::create(2025, 3, 5, 8, 0, 0, 'UTC'));
        $tz = 'UTC';

        /** @var User $user */
        $user = User::factory()->create();

        // Create an event on the selected day so hasEvent can be asserted
        $start = Carbon::create(2025, 3, 5, 14, 0, 0, 'UTC');
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

        $result = new GetDailyEventsService($user, '2025-03-05', $tz)->execute();

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
});
