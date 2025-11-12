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
});
