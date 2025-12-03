<?php

declare(strict_types=1);

use App\Enums\CalendarEventColoursEnum;
use App\Http\Resources\Calendar\CalendarEventWeekViewResource;
use App\Models\CalendarEvent;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Date;

mutates(CalendarEventWeekViewResource::class);

describe('CalendarEventWeekViewResource', function (): void {
    it('maps event to week view payload with dayNumber and timezone-aware fields', function (): void {
        $this->seed(RoleSeeder::class);

        $start = Date::create(2025, 8, 24, 22, 0, 0);
        $end = (clone $start)->addHour();

        $user = User::factory()->create();

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

        $resource = new CalendarEventWeekViewResource($event, 'Europe/Kyiv')->additional(['user' => $user]);
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

        $user = User::factory()->create();

        $event = CalendarEvent::factory()->create([
            'title'           => 'Noon Event',
            'start_date_time' => $start,
            'end_date_time'   => $end,
            'duration'        => 1800,
            'date'            => $start->format('Y-m-d'),
        ]);

        $event->calendarEventUsers()->attach($user->getKey(), ['colour' => CalendarEventColoursEnum::GREEN->value]);

        $resource = new CalendarEventWeekViewResource($event, config('app.timezone'))->additional(['user' => $user]);
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

        $user = User::factory()->create();

        $event = CalendarEvent::factory()->create([
            'title'           => '90 Min Event',
            'start_date_time' => $start,
            'end_date_time'   => $end,
            'duration'        => 5400,
            'date'            => $start->format('Y-m-d'),
        ]);

        $event->calendarEventUsers()->attach($user->getKey(), ['colour' => CalendarEventColoursEnum::RED->value]);

        $resource = new CalendarEventWeekViewResource($event, config('app.timezone'))->additional(['user' => $user]);
        $array = $resource->toArray(request());

        // durationIndex = 5400 * 12 / 3600 = 18
        expect($array['durationIndex'])->toBe(18);
    });

    it('calculates correct dayNumber for different days of week', function (): void {
        $this->seed(RoleSeeder::class);

        // Monday - 2025-08-25
        $start = Date::create(2025, 8, 25, 10, 0, 0);
        $end = (clone $start)->addHour();

        $user = User::factory()->create();

        $event = CalendarEvent::factory()->create([
            'title'           => 'Monday Event',
            'start_date_time' => $start,
            'end_date_time'   => $end,
            'duration'        => 3600,
            'date'            => $start->format('Y-m-d'),
        ]);

        $event->calendarEventUsers()->attach($user->getKey(), ['colour' => CalendarEventColoursEnum::BLUE->value]);

        $resource = new CalendarEventWeekViewResource($event, config('app.timezone'))->additional(['user' => $user]);
        $array = $resource->toArray(request());

        // Monday: format('w') = 1, so dayNumber = 1 + 1 = 2
        expect($array['dayNumber'])->toBe(2);
    });

    it('handles null colour when user is not attached', function (): void {
        $this->seed(RoleSeeder::class);

        $start = Date::create(2025, 8, 24, 10, 0, 0);
        $end = (clone $start)->addHour();

        $user = User::factory()->create();

        $event = CalendarEvent::factory()->create([
            'title'           => 'No User Event',
            'start_date_time' => $start,
            'end_date_time'   => $end,
            'duration'        => 3600,
            'date'            => $start->format('Y-m-d'),
        ]);

        // Don't attach user

        $resource = new CalendarEventWeekViewResource($event, config('app.timezone'))->additional(['user' => $user]);
        $array = $resource->toArray(request());

        expect($array['colour'])->toBeNull();
    });

    it('verifies exact secondsSinceMidnight calculation with specific time', function (): void {
        $this->seed(RoleSeeder::class);

        // Test at 14:30:45
        $start = Date::create(2025, 8, 24, 14, 30, 45);
        $end = (clone $start)->addMinutes(15);

        $user = User::factory()->create();

        $event = CalendarEvent::factory()->create([
            'title'           => 'Exact Time Test',
            'start_date_time' => $start,
            'end_date_time'   => $end,
            'duration'        => 900,
            'date'            => $start->format('Y-m-d'),
        ]);

        $event->calendarEventUsers()->attach($user->getKey(), ['colour' => CalendarEventColoursEnum::BLUE->value]);

        $resource = new CalendarEventWeekViewResource($event, config('app.timezone'))->additional(['user' => $user]);
        $array = $resource->toArray(request());

        // secondsSinceMidnight = 14*3600 + 30*60 + 45 = 50400 + 1800 + 45 = 52245
        // startIndex = (52245 * 6 / 3600) + 2 = 87.075 + 2 = 89 (truncated)
        expect($array['startIndex'])->toBe(89)
            ->and($array['durationIndex'])->toBe(3); // 900 * 12 / 3600 = 3
    });

    it('verifies exact calculation at midnight', function (): void {
        $this->seed(RoleSeeder::class);

        // Test at exactly midnight 00:00:00
        $start = Date::create(2025, 8, 24, 0, 0, 0);
        $end = (clone $start)->addMinutes(30);

        $user = User::factory()->create();

        $event = CalendarEvent::factory()->create([
            'title'           => 'Midnight Event',
            'start_date_time' => $start,
            'end_date_time'   => $end,
            'duration'        => 1800,
            'date'            => $start->format('Y-m-d'),
        ]);

        $event->calendarEventUsers()->attach($user->getKey(), ['colour' => CalendarEventColoursEnum::GREEN->value]);

        $resource = new CalendarEventWeekViewResource($event, config('app.timezone'))->additional(['user' => $user]);
        $array = $resource->toArray(request());

        // secondsSinceMidnight = 0*3600 + 0*60 + 0 = 0
        // startIndex = (0 * 6 / 3600) + 2 = 0 + 2 = 2
        expect($array['startIndex'])->toBe(2)
            ->and($array['durationIndex'])->toBe(6); // 1800 * 12 / 3600 = 6
    });

    it('verifies exact calculation with zero duration', function (): void {
        $this->seed(RoleSeeder::class);

        $start = Date::create(2025, 8, 24, 8, 0, 0);
        $end = clone $start; // Same time, zero duration

        $user = User::factory()->create();

        $event = CalendarEvent::factory()->create([
            'title'           => 'Zero Duration Event',
            'start_date_time' => $start,
            'end_date_time'   => $end,
            'duration'        => 0,
            'date'            => $start->format('Y-m-d'),
        ]);

        $event->calendarEventUsers()->attach($user->getKey(), ['colour' => CalendarEventColoursEnum::RED->value]);

        $resource = new CalendarEventWeekViewResource($event, config('app.timezone'))->additional(['user' => $user]);
        $array = $resource->toArray(request());

        // durationIndex = 0 * 12 / 3600 = 0
        expect($array['durationIndex'])->toBe(0);
    });

    it('verifies dayNumber calculation for Sunday', function (): void {
        $this->seed(RoleSeeder::class);

        // Sunday - 2025-08-24
        $start = Date::create(2025, 8, 24, 10, 0, 0);
        $end = (clone $start)->addHour();

        $user = User::factory()->create();

        $event = CalendarEvent::factory()->create([
            'title'           => 'Sunday Event',
            'start_date_time' => $start,
            'end_date_time'   => $end,
            'duration'        => 3600,
            'date'            => $start->format('Y-m-d'),
        ]);

        $event->calendarEventUsers()->attach($user->getKey(), ['colour' => CalendarEventColoursEnum::BLUE->value]);

        $resource = new CalendarEventWeekViewResource($event, config('app.timezone'))->additional(['user' => $user]);
        $array = $resource->toArray(request());

        // Sunday: format('w') = 0, so dayNumber = 0 + 1 = 1
        expect($array['dayNumber'])->toBe(1);
    });

    it('verifies dayNumber calculation for Saturday', function (): void {
        $this->seed(RoleSeeder::class);

        // Saturday - 2025-08-30
        $start = Date::create(2025, 8, 30, 10, 0, 0);
        $end = (clone $start)->addHour();

        $user = User::factory()->create();

        $event = CalendarEvent::factory()->create([
            'title'           => 'Saturday Event',
            'start_date_time' => $start,
            'end_date_time'   => $end,
            'duration'        => 3600,
            'date'            => $start->format('Y-m-d'),
        ]);

        $event->calendarEventUsers()->attach($user->getKey(), ['colour' => CalendarEventColoursEnum::YELLOW->value]);

        $resource = new CalendarEventWeekViewResource($event, config('app.timezone'))->additional(['user' => $user]);
        $array = $resource->toArray(request());

        // Saturday: format('w') = 6, so dayNumber = 6 + 1 = 7
        expect($array['dayNumber'])->toBe(7);
    });

    it('verifies integer casting for all calculated values', function (): void {
        $this->seed(RoleSeeder::class);

        // Use times that result in fractional calculations
        $start = Date::create(2025, 8, 24, 13, 27, 33);
        $end = (clone $start)->addMinutes(47);

        $user = User::factory()->create();

        $event = CalendarEvent::factory()->create([
            'title'           => 'Fractional Test',
            'start_date_time' => $start,
            'end_date_time'   => $end,
            'duration'        => 2820, // 47 minutes
            'date'            => $start->format('Y-m-d'),
        ]);

        $event->calendarEventUsers()->attach($user->getKey(), ['colour' => CalendarEventColoursEnum::PURPLE->value]);

        $resource = new CalendarEventWeekViewResource($event, config('app.timezone'))->additional(['user' => $user]);
        $array = $resource->toArray(request());

        // Verify all are integers
        expect($array['dayNumber'])->toBeInt()
            ->and($array['startIndex'])->toBeInt()
            ->and($array['durationIndex'])->toBeInt();
    });

    it('verifies exact multipliers in secondsSinceMidnight calculation (3600, 60, 1)', function (): void {
        $this->seed(RoleSeeder::class);

        // Use 01:01:01 to test all three components
        $start = Date::create(2025, 8, 24, 1, 1, 1);
        $end = (clone $start)->addHour();

        $user = User::factory()->create();

        $event = CalendarEvent::factory()->create([
            'title'           => 'Multiplier Test',
            'start_date_time' => $start,
            'end_date_time'   => $end,
            'duration'        => 3600,
            'date'            => $start->format('Y-m-d'),
        ]);

        $event->calendarEventUsers()->attach($user->getKey(), ['colour' => CalendarEventColoursEnum::BLUE->value]);

        $resource = new CalendarEventWeekViewResource($event, config('app.timezone'))->additional(['user' => $user]);
        $array = $resource->toArray(request());

        // secondsSinceMidnight = 1*3600 + 1*60 + 1 = 3661
        // startIndex = (3661 * 6 / 3600) + 2 = 6.1016... + 2 = 8 (truncated)
        expect($array['startIndex'])->toBe(8);
    });

    it('verifies exact durationIndex formula (duration * 12 / 3600)', function (): void {
        $this->seed(RoleSeeder::class);

        $start = Date::create(2025, 8, 24, 10, 0, 0);
        $end = (clone $start)->addMinutes(25); // 1500 seconds

        $user = User::factory()->create();

        $event = CalendarEvent::factory()->create([
            'title'           => 'Duration Formula Test',
            'start_date_time' => $start,
            'end_date_time'   => $end,
            'duration'        => 1500,
            'date'            => $start->format('Y-m-d'),
        ]);

        $event->calendarEventUsers()->attach($user->getKey(), ['colour' => CalendarEventColoursEnum::GREEN->value]);

        $resource = new CalendarEventWeekViewResource($event, config('app.timezone'))->additional(['user' => $user]);
        $array = $resource->toArray(request());

        // durationIndex = 1500 * 12 / 3600 = 18000 / 3600 = 5
        expect($array['durationIndex'])->toBe(5);
    });

    it('verifies exact startIndex formula ((seconds * 6 / 3600) + 2)', function (): void {
        $this->seed(RoleSeeder::class);

        $start = Date::create(2025, 8, 24, 6, 0, 0);
        $end = (clone $start)->addHour();

        $user = User::factory()->create();

        $event = CalendarEvent::factory()->create([
            'title'           => 'StartIndex Formula Test',
            'start_date_time' => $start,
            'end_date_time'   => $end,
            'duration'        => 3600,
            'date'            => $start->format('Y-m-d'),
        ]);

        $event->calendarEventUsers()->attach($user->getKey(), ['colour' => CalendarEventColoursEnum::RED->value]);

        $resource = new CalendarEventWeekViewResource($event, config('app.timezone'))->additional(['user' => $user]);
        $array = $resource->toArray(request());

        // secondsSinceMidnight = 6*3600 = 21600
        // startIndex = (21600 * 6 / 3600) + 2 = 36 + 2 = 38
        expect($array['startIndex'])->toBe(38);
    });

    it('verifies the +2 constant in startIndex calculation', function (): void {
        $this->seed(RoleSeeder::class);

        // Test at a time where removing +2 would give wrong result
        $start = Date::create(2025, 8, 24, 0, 10, 0);
        $end = (clone $start)->addMinutes(30);

        $user = User::factory()->create();

        $event = CalendarEvent::factory()->create([
            'title'           => 'Plus Two Test',
            'start_date_time' => $start,
            'end_date_time'   => $end,
            'duration'        => 1800,
            'date'            => $start->format('Y-m-d'),
        ]);

        $event->calendarEventUsers()->attach($user->getKey(), ['colour' => CalendarEventColoursEnum::YELLOW->value]);

        $resource = new CalendarEventWeekViewResource($event, config('app.timezone'))->additional(['user' => $user]);
        $array = $resource->toArray(request());

        // secondsSinceMidnight = 0*3600 + 10*60 + 0 = 600
        // startIndex = (600 * 6 / 3600) + 2 = 1 + 2 = 3
        // Without +2, it would be 1
        expect($array['startIndex'])->toBe(3);
    });

    it('verifies the +1 in dayNumber calculation (format w + 1)', function (): void {
        $this->seed(RoleSeeder::class);

        // Sunday is 0, should become 1
        $start = Date::create(2025, 8, 24, 10, 0, 0); // Sunday
        $end = (clone $start)->addHour();

        $user = User::factory()->create();

        $event = CalendarEvent::factory()->create([
            'title'           => 'DayNumber Plus One Test',
            'start_date_time' => $start,
            'end_date_time'   => $end,
            'duration'        => 3600,
            'date'            => $start->format('Y-m-d'),
        ]);

        $event->calendarEventUsers()->attach($user->getKey(), ['colour' => CalendarEventColoursEnum::BLUE->value]);

        $resource = new CalendarEventWeekViewResource($event, config('app.timezone'))->additional(['user' => $user]);
        $array = $resource->toArray(request());

        // Sunday: format('w') = 0, dayNumber = 0 + 1 = 1
        // Without +1, it would be 0
        expect($array['dayNumber'])->toBe(1);
    });

    it('verifies null-safe operator chain on colour retrieval', function (): void {
        $this->seed(RoleSeeder::class);

        $start = Date::create(2025, 8, 24, 10, 0, 0);
        $end = (clone $start)->addHour();

        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $event = CalendarEvent::factory()->create([
            'title'           => 'Null Safe Test',
            'start_date_time' => $start,
            'end_date_time'   => $end,
            'duration'        => 3600,
            'date'            => $start->format('Y-m-d'),
        ]);

        // Attach different user, not the one in additional
        $event->calendarEventUsers()->attach($otherUser->getKey(), ['colour' => CalendarEventColoursEnum::BLUE->value]);

        $resource = new CalendarEventWeekViewResource($event, config('app.timezone'))->additional(['user' => $user]);
        $array = $resource->toArray(request());

        // Should be null because user not found in relationship
        expect($array['colour'])->toBeNull();
    });

    it('verifies colour is retrieved correctly when user is attached', closure: function (): void {
        $this->seed(RoleSeeder::class);

        $start = Date::create(2025, 8, 24, 10, 0, 0);
        $end = (clone $start)->addHour();

        $user = User::factory()->create();

        $event = CalendarEvent::factory()->create([
            'title'           => 'Colour Retrieval Test',
            'start_date_time' => $start,
            'end_date_time'   => $end,
            'duration'        => 3600,
            'date'            => $start->format('Y-m-d'),
        ]);

        $event->calendarEventUsers()->attach($user->getKey(), ['colour' => CalendarEventColoursEnum::PURPLE->value]);

        $resource = new CalendarEventWeekViewResource($event, config('app.timezone'))->additional(['user' => $user]);
        $array = $resource->toArray(request());

        // Should get exact colour from pivot
        expect($array['colour'])->toBe(CalendarEventColoursEnum::PURPLE->value);
    });
});
