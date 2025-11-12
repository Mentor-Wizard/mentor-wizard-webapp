<?php

declare(strict_types=1);

use App\Enums\CalendarEventColoursEnum;
use App\Http\Resources\EventWeekViewResource;
use App\Models\CalendarEvent;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Date;

mutates(EventWeekViewResource::class);

describe('EventWeekViewResource', function (): void {
    it('maps event to week view payload with dayNumber and timezone-aware fields', function (): void {
        $this->seed(RoleSeeder::class);

        $start = Date::create(2025, 8, 24, 22, 0, 0);
        $end = (clone $start)->addHour();

        $user = App\Models\User::factory()->create();

        /** @var CalendarEvent $event */
        $event = CalendarEvent::factory()->create([
            'title'           => 'Week Resource Test',
            'start_date_time' => $start,
            'end_date_time'   => $end,
            'duration'        => $start?->diffInSeconds($end),
            'date'            => $start?->format('Y-m-d'),
            'type'            => 'individual',
            'web_link'        => 'https://example.com/week',
            'description'     => 'Week view description',
        ]);

        $event->calendarEventUsers()->attach($user->getKey(), [
            'colour' => CalendarEventColoursEnum::BLUE->value,
        ]);

        $resource = new EventWeekViewResource($event, 'Europe/Kyiv')->additional(['user' => $user]);
        $array = $resource->toArray(request());

        expect($array)
            ->toHaveKeys(['id', 'dayNumber', 'time', 'dateTime', 'durationIndex', 'startIndex', 'title', 'href', 'colour'])
            ->and($array['id'])->toBe($event->getKey())
            ->and($array['dayNumber'])->toBe(2) // Sunday -> 1
            ->and($array['time'])->toBe('1:00 AM')
            ->and($array['dateTime'])->toBe('2025-08-25"EEST"01:00:00')
            ->and($array['durationIndex'])->toBe(12)
            ->and($array['startIndex'])->toBe(8)
            ->and($array['title'])->toBe('Week Resource Test')
            ->and($array['href'])->toBe('https://example.com/week')
            ->and($array['colour'])->toBe(CalendarEventColoursEnum::BLUE->value);
    });

    it('calculates correct startIndex for different times of day', function (): void {
        $this->seed(RoleSeeder::class);

        // Test at noon (12:00:00)
        $start = Date::create(2025, 8, 24, 12, 0, 0);
        $end = (clone $start)->addMinutes(30);

        $user = App\Models\User::factory()->create();

        $event = CalendarEvent::factory()->create([
            'title'           => 'Noon Event',
            'start_date_time' => $start,
            'end_date_time'   => $end,
            'duration'        => 1800,
            'date'            => $start->format('Y-m-d'),
        ]);

        $event->calendarEventUsers()->attach($user->getKey(), ['colour' => CalendarEventColoursEnum::GREEN->value]);

        $resource = new EventWeekViewResource($event, 'UTC')->additional(['user' => $user]);
        $array = $resource->toArray(request());

        // secondsSinceMidnight = 12*3600 + 0*60 + 0 = 43200
        // startIndex = (43200 * 6 / 3600) + 2 = 72 + 2 = 74
        expect($array['startIndex'])->toBe(74)
            ->and($array['durationIndex'])->toBe(6); // 1800 * 12 / 3600 = 6
    });

    it('calculates correct durationIndex for different durations', function (): void {
        $this->seed(RoleSeeder::class);

        $start = Date::create(2025, 8, 24, 10, 0, 0);
        $end = (clone $start)->addMinutes(90); // 5400 seconds

        $user = App\Models\User::factory()->create();

        $event = CalendarEvent::factory()->create([
            'title'           => '90 Min Event',
            'start_date_time' => $start,
            'end_date_time'   => $end,
            'duration'        => 5400,
            'date'            => $start->format('Y-m-d'),
        ]);

        $event->calendarEventUsers()->attach($user->getKey(), ['colour' => CalendarEventColoursEnum::RED->value]);

        $resource = new EventWeekViewResource($event, 'UTC')->additional(['user' => $user]);
        $array = $resource->toArray(request());

        // durationIndex = 5400 * 12 / 3600 = 18
        expect($array['durationIndex'])->toBe(18);
    });

    it('calculates correct dayNumber for different days of week', function (): void {
        $this->seed(RoleSeeder::class);

        // Monday - 2025-08-25
        $start = Date::create(2025, 8, 25, 10, 0, 0);
        $end = (clone $start)->addHour();

        $user = App\Models\User::factory()->create();

        $event = CalendarEvent::factory()->create([
            'title'           => 'Monday Event',
            'start_date_time' => $start,
            'end_date_time'   => $end,
            'duration'        => 3600,
            'date'            => $start->format('Y-m-d'),
        ]);

        $event->calendarEventUsers()->attach($user->getKey(), ['colour' => CalendarEventColoursEnum::BLUE->value]);

        $resource = new EventWeekViewResource($event, 'UTC')->additional(['user' => $user]);
        $array = $resource->toArray(request());

        // Monday: format('w') = 1, so dayNumber = 1 + 1 = 2
        expect($array['dayNumber'])->toBe(2);
    });

    it('handles null colour when user is not attached', function (): void {
        $this->seed(RoleSeeder::class);

        $start = Date::create(2025, 8, 24, 10, 0, 0);
        $end = (clone $start)->addHour();

        $user = App\Models\User::factory()->create();

        $event = CalendarEvent::factory()->create([
            'title'           => 'No User Event',
            'start_date_time' => $start,
            'end_date_time'   => $end,
            'duration'        => 3600,
            'date'            => $start->format('Y-m-d'),
        ]);

        // Don't attach user

        $resource = new EventWeekViewResource($event, 'UTC')->additional(['user' => $user]);
        $array = $resource->toArray(request());

        expect($array['colour'])->toBeNull();
    });
});
