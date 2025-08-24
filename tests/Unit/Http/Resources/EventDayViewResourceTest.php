<?php

declare(strict_types=1);

use App\Enums\EventCalendarColoursEnum;
use App\Http\Resources\EventDayViewResource;
use App\Models\Event;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

mutates(EventDayViewResource::class);

describe('EventDayViewResource', function (): void {
    it('maps event to day view payload with timezone-aware fields', function (): void {
        // Fixed date that is in DST for Europe/Kyiv (EEST)
        $start = Carbon::create(2025, 8, 24, 22, 0, 0, 'Europe/Kyiv'); // 22:00
        $end = (clone $start)->addHour();

        /** @var Event $event */
        $event = Event::factory()->create([
            'title'           => 'Day Resource Test',
            'start_date_time' => $start,
            'end_date_time'   => $end,
            'duration'        => $start?->diffInSeconds($end), // 3600
            'date'            => $start?->format('Y-m-d'),
            'type'            => 'individual',
            'web_link'        => 'https://example.com/day',
            'description'     => 'Day view description',
        ]);

        $resource = new EventDayViewResource($event, 'Europe/Kyiv');
        $array = $resource->toArray(request());

        expect($array)
            ->toHaveKeys(['id', 'time', 'dateTime', 'durationIndex', 'startIndex', 'title', 'href', 'colour'])
            ->and($array['id'])->toBe($event->getKey())
            ->and($array['time'])->toBe('10:00 PM')
            ->and($array['dateTime'])->toBe('2025-08-24"EEST"22:00:00')
            ->and($array['durationIndex'])->toBe(12)
            ->and($array['startIndex'])->toBe(134)
            ->and($array['title'])->toBe('Day Resource Test')
            ->and($array['href'])->toBe('https://example.com/day')
            ->and(in_array(Arr::get($array, 'colour'), EventCalendarColoursEnum::values(), true))->toBeTrue();
    });
});
