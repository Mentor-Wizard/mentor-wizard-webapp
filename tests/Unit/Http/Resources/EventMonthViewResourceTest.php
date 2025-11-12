<?php

declare(strict_types=1);

use App\Http\Resources\EventMonthViewResource;
use App\Models\CalendarEvent;
use Illuminate\Support\Facades\Date;

mutates(EventMonthViewResource::class);

describe('EventMonthViewResource', function (): void {
    it('maps event to month view payload (name, time, datetime, href, id)', function (): void {
        // Use end-of-month evening to mirror existing page assertions (11PM formatting)
        $start = Date::create(2025, 8, 31, 23, 59, 0);
        $end = (clone $start)->addMinutes(30);

        $event = CalendarEvent::factory()->create([
            'title'           => 'Month Resource Test',
            'start_date_time' => $start,
            'end_date_time'   => $end,
            'duration'        => $start?->diffInSeconds($end),
            'date'            => $start?->format('Y-m-d'),
            'type'            => 'individual',
            'web_link'        => 'https://example.com/month',
            'description'     => 'Month view description',
        ]);

        $resource = new EventMonthViewResource($event)->additional(['timeZone' => 'UTC']);
        $array = $resource->toArray(request());

        expect($array)
            ->toHaveKeys(['name', 'time', 'datetime', 'href', 'id'])
            ->and($array['name'])->toBe('Month Resource Test')
            ->and($array['time'])->toBe('11PM')
            ->and($array['datetime'])->toBe('2025-08-31T23:59')
            ->and($array['href'])->toBe('https://example.com/month')
            ->and($array['id'])->toBe($event->getKey());
    });

    it('defaults to UTC timezone when timeZone is not provided in additional data', function (): void {
        $start = Date::create(2025, 8, 15, 14, 30, 0);
        $end = (clone $start)->addHour();

        $event = CalendarEvent::factory()->create([
            'title'           => 'Default Timezone Event',
            'start_date_time' => $start,
            'end_date_time'   => $end,
            'duration'        => 3600,
            'date'            => $start->format('Y-m-d'),
            'web_link'        => 'https://example.com/default',
        ]);

        // Don't provide timeZone in additional data
        $resource = new EventMonthViewResource($event);
        $array = $resource->toArray(request());

        // Should use UTC as default
        expect($array['time'])->toBe('2PM')
            ->and($array['datetime'])->toBe('2025-08-15T14:30');
    });

    it('uses custom timezone when provided', function (): void {
        $start = Date::create(2025, 8, 15, 22, 0, 0);
        $end = (clone $start)->addHour();

        $event = CalendarEvent::factory()->create([
            'title'           => 'Custom Timezone Event',
            'start_date_time' => $start,
            'end_date_time'   => $end,
            'duration'        => 3600,
            'date'            => $start->format('Y-m-d'),
            'web_link'        => 'https://example.com/custom',
        ]);

        $resource = new EventMonthViewResource($event)->additional(['timeZone' => 'Europe/Kyiv']);
        $array = $resource->toArray(request());

        // In Europe/Kyiv timezone, 22:00 UTC becomes 01:00 EEST next day
        expect($array['time'])->toBe('1AM')
            ->and($array['datetime'])->toBe('2025-08-16T01:00');
    });
});
