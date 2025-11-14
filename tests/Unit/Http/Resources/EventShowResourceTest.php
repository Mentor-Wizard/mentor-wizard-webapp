<?php

declare(strict_types=1);

use App\Enums\CalendarEventColoursEnum;
use App\Http\Resources\EventShowResource;
use App\Models\CalendarEvent;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Date;

mutates(EventShowResource::class);

describe('EventShowResource', function (): void {
    it('maps event to detailed payload', function (): void {
        $this->seed(RoleSeeder::class);

        $start = Date::create(2025, 8, 22, 9, 30, 0);
        $end = Date::create(2025, 8, 22, 11, 0, 0);

        $user = User::factory()->create();

        $event = CalendarEvent::factory()->create([
            'title'           => 'Show Resource Test',
            'start_date_time' => $start,
            'end_date_time'   => $end,
            'duration'        => $start?->diffInSeconds($end),
            'date'            => $start?->format('Y-m-d'),
            'type'            => 'individual',
            'web_link'        => 'https://example.com/show',
            'description'     => 'Desc',
        ]);

        $event->calendarEventUsers()->attach($user->getKey(), [
            'colour' => CalendarEventColoursEnum::BLUE->value,
        ]);

        $resource = new EventShowResource($event)->additional(['user' => $user, 'timezone' => config('app.timezone')]);
        $array = $resource->toArray(request());

        expect($array)
            ->toHaveKeys(['id', 'title', 'fromDateFormatted', 'fromDate', 'fromTime', 'toDate', 'type', 'toDateFormatted', 'toTime', 'duration', 'href', 'description', 'colour'])
            ->and($array['title'])->toBe('Show Resource Test')
            ->and($array['fromDateFormatted'])->toBe('2025-Aug-22')
            ->and($array['fromDate'])->toBe('2025-08-22')
            ->and($array['fromTime'])->toBe('09:30')
            ->and($array['toDate'])->toBe('2025-08-22')
            ->and($array['toDateFormatted'])->toBe('2025-Aug-22')
            ->and($array['toTime'])->toBe('11:00')
            ->and($array['duration'])->toBe('01:30')
            ->and($array['href'])->toBe('https://example.com/show')
            ->and($array['description'])->toBe('Desc')
            ->and($array['colour'])->toBe(CalendarEventColoursEnum::BLUE->value);
    });

    it('uses default timezone when timezone is not provided', function (): void {
        $this->seed(RoleSeeder::class);

        $start = Date::create(2025, 8, 22, 9, 30, 0);
        $end = Date::create(2025, 8, 22, 11, 0, 0);

        $user = User::factory()->create();

        $event = CalendarEvent::factory()->create([
            'title'           => 'Default TZ Event',
            'start_date_time' => $start,
            'end_date_time'   => $end,
            'duration'        => $start?->diffInSeconds($end),
            'date'            => $start?->format('Y-m-d'),
            'type'            => 'individual',
            'web_link'        => 'https://example.com/default',
            'description'     => 'Test',
        ]);

        $event->calendarEventUsers()->attach($user->getKey(), [
            'colour' => CalendarEventColoursEnum::GREEN->value,
        ]);

        // Don't provide timezone in additional
        $resource = new EventShowResource($event)->additional(['user' => $user]);
        $array = $resource->toArray(request());

        // Should use UTC as default
        expect($array['fromDate'])->toBe('2025-08-22')
            ->and($array['fromTime'])->toBe('09:30')
            ->and($array['toDate'])->toBe('2025-08-22')
            ->and($array['toTime'])->toBe('11:00');
    });

    it('returns null colour when user is not attached to event', function (): void {
        $this->seed(RoleSeeder::class);

        $start = Date::create(2025, 8, 22, 9, 30, 0);
        $end = Date::create(2025, 8, 22, 11, 0, 0);

        $user = User::factory()->create();

        $event = CalendarEvent::factory()->create([
            'title'           => 'No User Event',
            'start_date_time' => $start,
            'end_date_time'   => $end,
            'duration'        => $start?->diffInSeconds($end),
            'date'            => $start?->format('Y-m-d'),
            'type'            => 'individual',
            'web_link'        => 'https://example.com/nouser',
            'description'     => 'Test',
        ]);

        // Don't attach user to event

        $resource = new EventShowResource($event)->additional(['user' => $user, 'timezone' => config('app.timezone')]);
        $array = $resource->toArray(request());

        expect($array['colour'])->toBeNull();
    });

    it('returns null colour when user is null', function (): void {
        $this->seed(RoleSeeder::class);

        $start = Date::create(2025, 8, 22, 9, 30, 0);
        $end = Date::create(2025, 8, 22, 11, 0, 0);

        $event = CalendarEvent::factory()->create([
            'title'           => 'Null User Event',
            'start_date_time' => $start,
            'end_date_time'   => $end,
            'duration'        => $start?->diffInSeconds($end),
            'date'            => $start?->format('Y-m-d'),
            'type'            => 'individual',
            'web_link'        => 'https://example.com/nulluser',
            'description'     => 'Test',
        ]);

        // Don't provide user in additional
        $resource = new EventShowResource($event)->additional(['timezone' => config('app.timezone')]);
        $array = $resource->toArray(request());

        expect($array['colour'])->toBeNull();
    });

    it('returns null colour when calendarEventUsers relationship is null', function (): void {
        $this->seed(RoleSeeder::class);

        $start = Date::create(2025, 8, 22, 9, 30, 0);
        $end = Date::create(2025, 8, 22, 11, 0, 0);

        $user = User::factory()->create();

        $event = CalendarEvent::factory()->create([
            'title'           => 'No Relationship Event',
            'start_date_time' => $start,
            'end_date_time'   => $end,
            'duration'        => $start?->diffInSeconds($end),
            'date'            => $start?->format('Y-m-d'),
            'type'            => 'individual',
            'web_link'        => 'https://example.com/norel',
            'description'     => 'Test',
        ]);

        // Create resource without eager loading calendarEventUsers
        $resource = new EventShowResource($event)->additional(['user' => $user, 'timezone' => config('app.timezone')]);
        $array = $resource->toArray(request());

        expect($array['colour'])->toBeNull();
    });

    it('uses provided timezone to format dates differently', function (): void {
        $this->seed(RoleSeeder::class);

        // Create event at 22:00 UTC
        $start = Date::create(2025, 8, 22, 22, 0, 0);
        $end = Date::create(2025, 8, 22, 23, 30, 0);

        $user = User::factory()->create();

        $event = CalendarEvent::factory()->create([
            'title'           => 'Timezone Test Event',
            'start_date_time' => $start,
            'end_date_time'   => $end,
            'duration'        => $start?->diffInSeconds($end),
            'date'            => $start?->format('Y-m-d'),
            'type'            => 'individual',
            'web_link'        => 'https://example.com/tz',
            'description'     => 'Test',
        ]);

        // Test with Asia/Tokyo timezone (UTC+9)
        $resourceTokyo = new EventShowResource($event)->additional(['user' => $user, 'timezone' => 'Asia/Tokyo']);
        $arrayTokyo = $resourceTokyo->toArray(request());

        // In Tokyo, 22:00 UTC = 07:00 next day
        expect($arrayTokyo['fromTime'])->toBe('07:00')
            ->and($arrayTokyo['fromDate'])->toBe('2025-08-23');

        // Test with America/New_York timezone (UTC-4)
        $resourceNY = new EventShowResource($event)->additional(['user' => $user, 'timezone' => 'America/New_York']);
        $arrayNY = $resourceNY->toArray(request());

        // In New York, 22:00 UTC = 18:00 same day
        expect($arrayNY['fromTime'])->toBe('18:00')
            ->and($arrayNY['fromDate'])->toBe('2025-08-22');
    });

    it('handles null at each step of calendarEventUsers chain', function (): void {
        $this->seed(RoleSeeder::class);

        $start = Date::create(2025, 8, 22, 9, 30, 0);
        $end = Date::create(2025, 8, 22, 11, 0, 0);

        $user = User::factory()->create();
        $user2 = User::factory()->create();

        $event = CalendarEvent::factory()->create([
            'title'           => 'Chain Test Event',
            'start_date_time' => $start,
            'end_date_time'   => $end,
            'duration'        => $start?->diffInSeconds($end),
            'date'            => $start?->format('Y-m-d'),
            'type'            => 'individual',
            'web_link'        => 'https://example.com/chain',
            'description'     => 'Test',
        ]);

        // Attach a different user with a colour, but query with user who isn't attached
        $event->calendarEventUsers()->attach($user2->getKey(), [
            'colour' => CalendarEventColoursEnum::RED->value,
        ]);

        // Test with user who isn't in the relationship
        $resource = new EventShowResource($event->fresh())->additional(['user' => $user, 'timezone' => config('app.timezone')]);
        $array = $resource->toArray(request());

        // Should be null because where() filters out the attached user
        expect($array['colour'])->toBeNull();

        // Test with the attached user
        $resource2 = new EventShowResource($event->fresh())->additional(['user' => $user2, 'timezone' => config('app.timezone')]);
        $array2 = $resource2->toArray(request());

        // Should have the colour
        expect($array2['colour'])->toBe(CalendarEventColoursEnum::RED->value);
    });
});
