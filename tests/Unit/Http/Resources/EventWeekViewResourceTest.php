<?php

declare(strict_types=1);

use App\Enums\CalendarEventColoursEnum;
use App\Http\Resources\EventWeekViewResource;
use App\Models\CalendarEvent;
use Database\Seeders\RoleSeeder;

mutates(EventWeekViewResource::class);

describe('EventWeekViewResource', function (): void {
    it('maps event to week view payload with dayNumber and timezone-aware fields', function (): void {
        $this->seed(RoleSeeder::class);

        $start = Illuminate\Support\Facades\Date::create(2025, 8, 24, 22, 0, 0);
        $end = (clone $start)->addHour();

        $user = App\Models\User::factory()->create();

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

        $resource = new EventWeekViewResource($event, 'Europe/Kyiv')->additional(['user' => $user]);
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
});
