<?php

declare(strict_types=1);

use App\Http\Resources\EventShowResource;
use App\Models\Event;
use Illuminate\Support\Carbon;

mutates(EventShowResource::class);

describe('EventShowResource', function (): void {
    it('maps event to detailed payload', function (): void {
        $start = Carbon::create(2025, 8, 22, 9, 30, 0);
        $end = Carbon::create(2025, 8, 22, 11, 0, 0);

        $event = Event::factory()->create([
            'title'           => 'Show Resource Test',
            'start_date_time' => $start,
            'end_date_time'   => $end,
            'duration'        => $start?->diffInSeconds($end),
            'date'            => $start?->format('Y-m-d'),
            'type'            => 'individual',
            'web_link'        => 'https://example.com/show',
            'description'     => 'Desc',
        ]);

        $resource = new EventShowResource($event);
        $array = $resource->toArray(request());

        expect($array)
            ->toHaveKeys(['id', 'title', 'fromDateFormatted', 'fromDate', 'fromTime', 'toDate', 'type', 'toDateFormatted', 'toTime', 'duration', 'href', 'description'])
            ->and($array['title'])->toBe('Show Resource Test')
            ->and($array['fromDateFormatted'])->toBe('2025-Aug-22')
            ->and($array['fromDate'])->toBe('2025-08-22')
            ->and($array['fromTime'])->toBe('09:30')
            ->and($array['toDate'])->toBe('2025-08-22')
            ->and($array['toDateFormatted'])->toBe('2025-Aug-22')
            ->and($array['toTime'])->toBe('11:00')
            ->and($array['duration'])->toBe('01:30')
            ->and($array['href'])->toBe('https://example.com/show')
            ->and($array['description'])->toBe('Desc');
    });
});
