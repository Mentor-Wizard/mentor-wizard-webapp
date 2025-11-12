<?php

declare(strict_types=1);

use App\Models\CalendarEvent;
use App\Models\User;
use App\Services\Calendar\GetMonthEventsService;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Date;

mutates(GetMonthEventsService::class);

describe('GetMonthEventsService Service', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
    });

    it('includes empty day entries with events key for month calendar and sets flags for event day using service (timezone aware)', function (): void {
        // Set application now to UTC, but test conversion by using Europe/Kyiv for building
        Date::setTestNow(Date::create(2025, 2, 10, 9, 0, 0, 'UTC'));
        $tz = 'Europe/Kyiv';

        /** @var User $user */
        $user = User::factory()->create();

        // Create an event for today in UTC (will be converted in resource/service as needed)
        $startUtc = Date::create(2025, 2, 10, 10, 0, 0, 'UTC');
        $endUtc = (clone $startUtc)->addHour();

        /** @var CalendarEvent $event */
        $event = CalendarEvent::query()->create([
            'title'           => 'Test CalendarEvent',
            'status'          => 'confirmed',
            'start_date_time' => $startUtc,
            'end_date_time'   => $endUtc,
            'duration'        => $endUtc->diffInSeconds($startUtc),
            'date'            => $startUtc->format('Y-m-d'),
            'type'            => 'individual',
        ]);

        $user->calendarEvents()->attach($event->getKey());

        $result = new GetMonthEventsService($user, '2025-02-10', $tz)->execute();

        expect($result)->toHaveKeys(['calendarView', 'hasEventsBefore', 'hasEventsAfter']);

        $calendarView = $result['calendarView'];
        expect($calendarView)->toBeArray()->not->toBeEmpty();

        // Find the entry for the event date
        $eventEntry = collect($calendarView)->firstWhere('date', '2025-02-10');

        expect($eventEntry)
            ->toBeArray()
            ->and($eventEntry['events'] ?? null)->toBeArray()->not->toBeEmpty()
            ->and($eventEntry['isCurrentMonth'] ?? null)->toBeTrue()
            ->and($eventEntry['isSelected'] ?? null)->toBeTrue()
            ->and($eventEntry['isToday'] ?? null)->toBeTrue();

        // Ensure a day without events includes empty events array
        $emptyDay = collect($calendarView)
            ->first(fn (array $day): bool => $day['date'] !== '2025-02-10' && ($day['events'] ?? null) === []);

        expect($emptyDay)
            ->toBeArray()
            ->and($emptyDay['events'])->toBeArray()->toBe([]);
    });
});
