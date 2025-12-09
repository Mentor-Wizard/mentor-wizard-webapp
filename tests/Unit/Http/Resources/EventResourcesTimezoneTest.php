<?php

declare(strict_types=1);

use App\Enums\CalendarEventRoleEnum;
use App\Enums\RoleEnum;
use App\Http\Resources\Calendar\CalendarEventDayViewResource;
use App\Http\Resources\Calendar\CalendarEventWeekViewResource;
use App\Models\CalendarEvent;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Date;
use Spatie\Permission\Models\Role;

mutates(CalendarEventDayViewResource::class);
mutates(CalendarEventWeekViewResource::class);

describe('CalendarEvent Resources with timezone', function (): void {
    it('applies timezone adjustment for week view', function (): void {
        $startUtc = Date::create(2025, 8, 24, 22, 0, 0, 'UTC');
        $endUtc = (clone $startUtc)->addHour();

        $event = CalendarEvent::factory()->create([
            'title'           => 'TZ Week',
            'start_date_time' => $startUtc,
            'end_date_time'   => $endUtc,
            'duration'        => $startUtc?->diffInSeconds($endUtc),
            'date'            => $startUtc?->format('Y-m-d'),
            'type'            => 'individual',
            'web_link'        => 'https://example.com/tz-week',
            'description'     => 'tz',
        ]);
        $this->seed(RoleSeeder::class);
        $this->user = User::factory()->create();
        $this->user->assignRole(Role::findByName(RoleEnum::MENTOR->value));

        $event->calendarEventUsers()->attach($this->user->getKey(),
            ['role'      => CalendarEventRoleEnum::HOST,
                'colour' => 'blue']);

        $resource = new CalendarEventWeekViewResource($event, 'Europe/Kyiv')->additional(['user' => $this->user]);

        $array = $resource->toArray(request());

        expect($array['time'])->toBe('1:00 AM')
            ->and($array['dayNumber'])->toBe(2)
            ->and($array['dateTime'])->toBe('2025-08-25"EEST"01:00:00')
            ->and($array['durationIndex'])->toBe(12)
            ->and($array['startIndex'])->toBe(8);
    });

    it('applies timezone adjustment for day view', function (): void {
        $startUtc = Date::create(2025, 8, 24, 22, 0, 0, 'UTC');
        $endUtc = (clone $startUtc)->addHour();

        /** @var CalendarEvent $event */
        $event = CalendarEvent::factory()->create([
            'title'           => 'TZ Day',
            'start_date_time' => $startUtc,
            'end_date_time'   => $endUtc,
            'duration'        => $startUtc?->diffInSeconds($endUtc),
            'date'            => $startUtc?->format('Y-m-d'),
            'type'            => 'individual',
            'web_link'        => 'https://example.com/tz-day',
            'description'     => 'tz',
        ]);

        $resource = new CalendarEventDayViewResource($event, 'Europe/Kyiv');
        $array = $resource->toArray(request());

        expect($array['time'])->toBe('1:00 AM')
            ->and($array['dateTime'])->toBe('2025-08-25"EEST"01:00:00')
            ->and($array['durationIndex'])->toBe(12)
            ->and($array['startIndex'])->toBe(8);
    });
});
