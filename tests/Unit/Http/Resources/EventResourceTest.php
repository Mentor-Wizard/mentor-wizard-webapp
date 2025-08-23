<?php

declare(strict_types=1);

use App\Http\Resources\EventResource;
use App\Models\Event;
use Illuminate\Support\Carbon;

mutates(EventResource::class);

describe('EventResource', function (): void {
    it('maps event to expected payload', function (): void {
        $start = Carbon::create(2025, 8, 20, 22, 0, 0);
        $end = Carbon::create(2025, 8, 20, 23, 0, 0);

        $event = Event::factory()->create([
            'title'           => 'Resource Test',
            'start_date_time' => $start,
            'end_date_time'   => $end,
            'date'            => $start?->format('Y-m-d'),
            'web_link'        => 'https://example.com',
        ]);

        $resource = new EventResource($event);
        $array = $resource->toArray(request());

        expect($array)
            ->toHaveKeys(['name', 'time', 'datetime', 'href', 'id'])
            ->and($array['name'])->toBe('Resource Test')
            ->and($array['time'])->toBe('10PM')
            ->and($array['datetime'])->toBe('2025-08-20T22:00')
            ->and($array['href'])->toBe('https://example.com')
            ->and($array['id'])->toBe($event->unique_id);
    });
});
