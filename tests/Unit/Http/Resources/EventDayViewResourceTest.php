<?php

declare(strict_types=1);

use App\Enums\CalendarEventColoursEnum;
use App\Http\Resources\EventDayViewResource;
use App\Models\CalendarEvent;
use Illuminate\Support\Arr;

mutates(EventDayViewResource::class);

describe('EventDayViewResource', function (): void {
    it('maps event to day view payload with timezone-aware fields', function (): void {
        $start = Illuminate\Support\Facades\Date::now()->addDays(2)->setTime(22, 0, 0, 0);
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
            ->and($array['dateTime'])->toBe(Illuminate\Support\Facades\Date::now()->addDays(2)->format('Y-m-d').'"UTC"22:00:00')
            ->and($array['durationIndex'])->toBe(12)
            ->and($array['startIndex'])->toBe(134)
            ->and($array['title'])->toBe('Day Resource Test')
            ->and($array['href'])->toBe('https://example.com/day')
            ->and(in_array(Arr::get($array, 'colour'), CalendarEventColoursEnum::values(), true))->toBeTrue();
    });
});
