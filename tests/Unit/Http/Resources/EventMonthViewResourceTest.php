<?php

declare(strict_types=1);

use App\Http\Resources\EventMonthViewResource;
use App\Models\CalendarEvent;

mutates(EventMonthViewResource::class);

describe('EventMonthViewResource', function (): void {
    it('maps event to month view payload (name, time, datetime, href, id)', function (): void {
        // Use end-of-month evening to mirror existing page assertions (11PM formatting)
        $start = Illuminate\Support\Facades\Date::create(2025, 8, 31, 23, 59, 0);
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
});
