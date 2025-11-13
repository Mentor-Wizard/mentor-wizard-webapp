<?php

declare(strict_types=1);

use App\Enums\CalendarEventColoursEnum;
use App\Http\Resources\EventDayViewResource;
use App\Models\CalendarEvent;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Date;

mutates(EventDayViewResource::class);

describe('EventDayViewResource', function (): void {
    it('maps event to day view payload with timezone-aware fields', function (): void {
        $start = Date::now()->addDays(2)->setTime(22, 0, 0, 0);
        $end = (clone $start)->addHour();

        $event = CalendarEvent::factory()->create([
            'title'           => 'Day Resource Test',
            'start_date_time' => $start,
            'end_date_time'   => $end,
            'duration'        => $start?->diffInSeconds($end), // 3600
            'date'            => $start?->format('Y-m-d'),
            'type'            => 'individual',
            'web_link'        => 'https://example.com/day',
            'description'     => 'Day view description',
        ]);

        $resource = new EventDayViewResource($event);
        $array = $resource->toArray(request());

        expect($array)
            ->toHaveKeys(['id', 'time', 'dateTime', 'durationIndex', 'startIndex', 'title', 'href', 'colour'])
            ->and($array['id'])->toBe($event->getKey())
            ->and($array['time'])->toBe('10:00 PM')
            ->and($array['dateTime'])->toBe(Date::now()->addDays(2)->format('Y-m-d').'"UTC"22:00:00')
            ->and($array['durationIndex'])->toBe(12)
            ->and($array['startIndex'])->toBe(134)
            ->and($array['title'])->toBe('Day Resource Test')
            ->and($array['href'])->toBe('https://example.com/day')
            ->and(in_array(Arr::get($array, 'colour'), CalendarEventColoursEnum::values(), true))->toBeTrue();
    });

    it('calculates correct startIndex for noon event', function (): void {
        $start = Date::create(2025, 8, 24, 12, 0, 0);
        $end = (clone $start)->addMinutes(30);

        $event = CalendarEvent::factory()->create([
            'title'           => 'Noon Event',
            'start_date_time' => $start,
            'end_date_time'   => $end,
            'duration'        => 1800,
            'date'            => $start->format('Y-m-d'),
        ]);

        $resource = new EventDayViewResource($event, 'UTC');
        $array = $resource->toArray(request());

        // secondsSinceMidnight = 12*3600 + 0*60 + 0 = 43200
        // startIndex = (43200 * 6 / 3600) + 2 = 72 + 2 = 74
        expect($array['startIndex'])->toBe(74)
            ->and($array['durationIndex'])->toBe(6); // 1800 * 12 / 3600 = 6
    });

    it('calculates correct durationIndex for 90 minute event', function (): void {
        $start = Date::create(2025, 8, 24, 10, 0, 0);
        $end = (clone $start)->addMinutes(90);

        $event = CalendarEvent::factory()->create([
            'title'           => '90 Min Event',
            'start_date_time' => $start,
            'end_date_time'   => $end,
            'duration'        => 5400,
            'date'            => $start->format('Y-m-d'),
        ]);

        $resource = new EventDayViewResource($event, 'UTC');
        $array = $resource->toArray(request());

        // durationIndex = 5400 * 12 / 3600 = 18
        expect($array['durationIndex'])->toBe(18);
    });

    it('calculates correct startIndex for morning event', function (): void {
        $start = Date::create(2025, 8, 24, 8, 30, 0);
        $end = (clone $start)->addMinutes(45);

        $event = CalendarEvent::factory()->create([
            'title'           => 'Morning Event',
            'start_date_time' => $start,
            'end_date_time'   => $end,
            'duration'        => 2700,
            'date'            => $start->format('Y-m-d'),
        ]);

        $resource = new EventDayViewResource($event, 'UTC');
        $array = $resource->toArray(request());

        // secondsSinceMidnight = 8*3600 + 30*60 + 0 = 28800 + 1800 = 30600
        // startIndex = (30600 * 6 / 3600) + 2 = 51 + 2 = 53
        expect($array['startIndex'])->toBe(53)
            ->and($array['durationIndex'])->toBe(9); // 2700 * 12 / 3600 = 9
    });

    it('verifies exact calculation with seconds included', function (): void {
        // Test at 14:30:45
        $start = Date::create(2025, 8, 24, 14, 30, 45);
        $end = (clone $start)->addMinutes(15);

        $event = CalendarEvent::factory()->create([
            'title'           => 'Exact Time Test',
            'start_date_time' => $start,
            'end_date_time'   => $end,
            'duration'        => 900,
            'date'            => $start->format('Y-m-d'),
        ]);

        $resource = new EventDayViewResource($event, 'UTC');
        $array = $resource->toArray(request());

        // secondsSinceMidnight = 14*3600 + 30*60 + 45 = 50400 + 1800 + 45 = 52245
        // startIndex = (52245 * 6 / 3600) + 2 = 87.075 + 2 = 89 (truncated)
        expect($array['startIndex'])->toBe(89)
            ->and($array['durationIndex'])->toBe(3); // 900 * 12 / 3600 = 3
    });

    it('verifies exact calculation at midnight', function (): void {
        // Test at exactly midnight 00:00:00
        $start = Date::create(2025, 8, 24, 0, 0, 0);
        $end = (clone $start)->addMinutes(30);

        $event = CalendarEvent::factory()->create([
            'title'           => 'Midnight Event',
            'start_date_time' => $start,
            'end_date_time'   => $end,
            'duration'        => 1800,
            'date'            => $start->format('Y-m-d'),
        ]);

        $resource = new EventDayViewResource($event, 'UTC');
        $array = $resource->toArray(request());

        // secondsSinceMidnight = 0*3600 + 0*60 + 0 = 0
        // startIndex = (0 * 6 / 3600) + 2 = 0 + 2 = 2
        expect($array['startIndex'])->toBe(2)
            ->and($array['durationIndex'])->toBe(6); // 1800 * 12 / 3600 = 6
    });

    it('verifies exact calculation with zero duration', function (): void {
        $start = Date::create(2025, 8, 24, 8, 0, 0);
        $end = clone $start; // Same time, zero duration

        $event = CalendarEvent::factory()->create([
            'title'           => 'Zero Duration Event',
            'start_date_time' => $start,
            'end_date_time'   => $end,
            'duration'        => 0,
            'date'            => $start->format('Y-m-d'),
        ]);

        $resource = new EventDayViewResource($event, 'UTC');
        $array = $resource->toArray(request());

        // durationIndex = 0 * 12 / 3600 = 0
        expect($array['durationIndex'])->toBe(0);
    });

    it('verifies integer casting for all calculated values', function (): void {
        // Use times that result in fractional calculations
        $start = Date::create(2025, 8, 24, 13, 27, 33);
        $end = (clone $start)->addMinutes(47);

        $event = CalendarEvent::factory()->create([
            'title'           => 'Fractional Test',
            'start_date_time' => $start,
            'end_date_time'   => $end,
            'duration'        => 2820, // 47 minutes
            'date'            => $start->format('Y-m-d'),
        ]);

        $resource = new EventDayViewResource($event, 'UTC');
        $array = $resource->toArray(request());

        // Verify all are integers
        expect($array['startIndex'])->toBeInt()
            ->and($array['durationIndex'])->toBeInt();
    });

    it('uses timezone from constructor parameter', function (): void {
        $start = Date::create(2025, 8, 24, 22, 0, 0);
        $end = (clone $start)->addHour();

        $event = CalendarEvent::factory()->create([
            'title'           => 'Timezone Test',
            'start_date_time' => $start,
            'end_date_time'   => $end,
            'duration'        => 3600,
            'date'            => $start->format('Y-m-d'),
        ]);

        $resource = new EventDayViewResource($event, 'Europe/Kyiv');
        $array = $resource->toArray(request());

        // In Europe/Kyiv timezone, 22:00 UTC becomes 01:00 EEST next day
        expect($array['dateTime'])->toContain('EEST')
            ->and($array['time'])->toBe('1:00 AM');
    });

    it('defaults to UTC when no timezone provided', function (): void {
        $start = Date::create(2025, 8, 24, 15, 0, 0);
        $end = (clone $start)->addHour();

        $event = CalendarEvent::factory()->create([
            'title'           => 'Default Timezone Test',
            'start_date_time' => $start,
            'end_date_time'   => $end,
            'duration'        => 3600,
            'date'            => $start->format('Y-m-d'),
        ]);

        $resource = new EventDayViewResource($event); // No timezone parameter
        $array = $resource->toArray(request());

        expect($array['dateTime'])->toContain('UTC')
            ->and($array['time'])->toBe('3:00 PM');
    });

    it('verifies exact multipliers in secondsSinceMidnight calculation (3600, 60, 1)', function (): void {
        // Use 01:01:01 to test all three components
        $start = Date::create(2025, 8, 24, 1, 1, 1);
        $end = (clone $start)->addHour();

        $event = CalendarEvent::factory()->create([
            'title'           => 'Multiplier Test',
            'start_date_time' => $start,
            'end_date_time'   => $end,
            'duration'        => 3600,
            'date'            => $start->format('Y-m-d'),
        ]);

        $resource = new EventDayViewResource($event, 'UTC');
        $array = $resource->toArray(request());

        // secondsSinceMidnight = 1*3600 + 1*60 + 1 = 3661
        // startIndex = (3661 * 6 / 3600) + 2 = 6.1016... + 2 = 8 (truncated)
        expect($array['startIndex'])->toBe(8);
    });

    it('verifies exact durationIndex formula (duration * 12 / 3600)', function (): void {
        $start = Date::create(2025, 8, 24, 10, 0, 0);
        $end = (clone $start)->addMinutes(25); // 1500 seconds

        $event = CalendarEvent::factory()->create([
            'title'           => 'Duration Formula Test',
            'start_date_time' => $start,
            'end_date_time'   => $end,
            'duration'        => 1500,
            'date'            => $start->format('Y-m-d'),
        ]);

        $resource = new EventDayViewResource($event, 'UTC');
        $array = $resource->toArray(request());

        // durationIndex = 1500 * 12 / 3600 = 18000 / 3600 = 5
        expect($array['durationIndex'])->toBe(5);
    });

    it('verifies exact startIndex formula ((seconds * 6 / 3600) + 2)', function (): void {
        $start = Date::create(2025, 8, 24, 6, 0, 0);
        $end = (clone $start)->addHour();

        $event = CalendarEvent::factory()->create([
            'title'           => 'StartIndex Formula Test',
            'start_date_time' => $start,
            'end_date_time'   => $end,
            'duration'        => 3600,
            'date'            => $start->format('Y-m-d'),
        ]);

        $resource = new EventDayViewResource($event, 'UTC');
        $array = $resource->toArray(request());

        // secondsSinceMidnight = 6*3600 = 21600
        // startIndex = (21600 * 6 / 3600) + 2 = 36 + 2 = 38
        expect($array['startIndex'])->toBe(38);
    });

    it('verifies the +2 constant in startIndex calculation', function (): void {
        // Test at a time where removing +2 would give wrong result
        $start = Date::create(2025, 8, 24, 0, 10, 0);
        $end = (clone $start)->addMinutes(30);

        $event = CalendarEvent::factory()->create([
            'title'           => 'Plus Two Test',
            'start_date_time' => $start,
            'end_date_time'   => $end,
            'duration'        => 1800,
            'date'            => $start->format('Y-m-d'),
        ]);

        $resource = new EventDayViewResource($event, 'UTC');
        $array = $resource->toArray(request());

        // secondsSinceMidnight = 0*3600 + 10*60 + 0 = 600
        // startIndex = (600 * 6 / 3600) + 2 = 1 + 2 = 3
        // Without +2, it would be 1
        expect($array['startIndex'])->toBe(3);
    });

    it('verifies integer casts prevent decimal values', function (): void {
        // Use time that creates fractional intermediate values
        $start = Date::create(2025, 8, 24, 2, 33, 47);
        $end = (clone $start)->addMinutes(17);

        $event = CalendarEvent::factory()->create([
            'title'           => 'Integer Cast Test',
            'start_date_time' => $start,
            'end_date_time'   => $end,
            'duration'        => 1020,
            'date'            => $start->format('Y-m-d'),
        ]);

        $resource = new EventDayViewResource($event, 'UTC');
        $array = $resource->toArray(request());

        // All values must be integers, not floats
        expect($array['startIndex'])->toBeInt()
            ->and($array['durationIndex'])->toBeInt()
            // Verify exact values to catch off-by-one from missing casts
            ->and($array['startIndex'])->toBe(17) // (9227 * 6 / 3600) + 2 = 15.378... + 2 = 17
            ->and($array['durationIndex'])->toBe(3); // 1020 * 12 / 3600 = 3.4 = 3
    });

    it('verifies hour component integer cast in secondsSinceMidnight', function (): void {
        // Specific time to test hour conversion
        $start = Date::create(2025, 8, 24, 5, 0, 0);
        $end = (clone $start)->addMinutes(30);

        $event = CalendarEvent::factory()->create([
            'title'           => 'Hour Cast Test',
            'start_date_time' => $start,
            'end_date_time'   => $end,
            'duration'        => 1800,
            'date'            => $start->format('Y-m-d'),
        ]);

        $resource = new EventDayViewResource($event, 'UTC');
        $array = $resource->toArray(request());

        // secondsSinceMidnight = 5*3600 = 18000
        // startIndex = (18000 * 6 / 3600) + 2 = 30 + 2 = 32
        expect($array['startIndex'])->toBe(32);
    });

    it('verifies minute component integer cast in secondsSinceMidnight', function (): void {
        // Specific time to test minute conversion
        $start = Date::create(2025, 8, 24, 0, 45, 0);
        $end = (clone $start)->addMinutes(15);

        $event = CalendarEvent::factory()->create([
            'title'           => 'Minute Cast Test',
            'start_date_time' => $start,
            'end_date_time'   => $end,
            'duration'        => 900,
            'date'            => $start->format('Y-m-d'),
        ]);

        $resource = new EventDayViewResource($event, 'UTC');
        $array = $resource->toArray(request());

        // secondsSinceMidnight = 0*3600 + 45*60 + 0 = 2700
        // startIndex = (2700 * 6 / 3600) + 2 = 4.5 + 2 = 6 (truncated)
        expect($array['startIndex'])->toBe(6);
    });

    it('verifies second component integer cast in secondsSinceMidnight', function (): void {
        // Specific time to test second conversion
        $start = Date::create(2025, 8, 24, 0, 0, 59);
        $end = (clone $start)->addMinutes(10);

        $event = CalendarEvent::factory()->create([
            'title'           => 'Second Cast Test',
            'start_date_time' => $start,
            'end_date_time'   => $end,
            'duration'        => 600,
            'date'            => $start->format('Y-m-d'),
        ]);

        $resource = new EventDayViewResource($event, 'UTC');
        $array = $resource->toArray(request());

        // secondsSinceMidnight = 0*3600 + 0*60 + 59 = 59
        // startIndex = (59 * 6 / 3600) + 2 = 0.0983... + 2 = 2 (truncated)
        expect($array['startIndex'])->toBe(2);
    });
});
