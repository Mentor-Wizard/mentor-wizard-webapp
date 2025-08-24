<?php

declare(strict_types=1);

use App\Enums\EventCalendarColoursEnum;
use App\Http\Resources\EventWeekViewResource;
use App\Models\Event;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

mutates(EventWeekViewResource::class);

describe('EventWeekViewResource', function (): void {
    it('maps event to week view payload with dayNumber and timezone-aware fields', function (): void {
        // Sunday, August 24, 2025 is a Sunday
        $start = Carbon::create(2025, 8, 24, 22, 0, 0, 'Europe/Kyiv');
        $end = (clone $start)->addHour();

        /** @var Event $event */
        $event = Event::factory()->create([
            'title'           => 'Week Resource Test',
            'start_date_time' => $start,
            'end_date_time'   => $end,
            'duration'        => $start?->diffInSeconds($end),
            'date'            => $start?->format('Y-m-d'),
            'type'            => 'individual',
            'web_link'        => 'https://example.com/week',
            'description'     => 'Week view description',
        ]);

        $resource = new EventWeekViewResource($event, 'Europe/Kyiv');
        $array = $resource->toArray(request());

        expect($array)
            ->toHaveKeys(['id', 'dayNumber', 'time', 'dateTime', 'durationIndex', 'startIndex', 'title', 'href', 'colour'])
            ->and($array['id'])->toBe($event->getKey())
            ->and($array['dayNumber'])->toBe(1) // Sunday -> 1
            ->and($array['time'])->toBe('10:00 PM')
            ->and($array['dateTime'])->toBe('2025-08-24"EEST"22:00:00')
            ->and($array['durationIndex'])->toBe(12)
            ->and($array['startIndex'])->toBe(134)
            ->and($array['title'])->toBe('Week Resource Test')
            ->and($array['href'])->toBe('https://example.com/week')
            ->and(in_array(Arr::get($array, 'colour'), EventCalendarColoursEnum::values(), true))->toBeTrue();
    });
});
