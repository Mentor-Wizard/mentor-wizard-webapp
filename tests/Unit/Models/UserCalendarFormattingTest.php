<?php

declare(strict_types=1);

use App\Actions\Calendar\Services\GetDailyEvents;
use App\Actions\Calendar\Services\GetMonthEvents;
use App\Actions\Calendar\Services\GetWeeklyEvents;
use App\Models\Event;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Carbon;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
});

it('sets flags in week formatted calendar (isCurrentMonth, isSelected, isToday) using service', function (): void {
    // Freeze time to ensure deterministic behaviour
    Carbon::setTestNow(Carbon::create(2025, 1, 15, 12, 0, 0, 'UTC'));
    $tz = 'UTC';

    /** @var User $user */
    $user = User::factory()->create();

    $date = '2025-01-15'; // Wednesday

    $result = new GetWeeklyEvents($user, $date, $tz)->execute();

    expect($result)
        ->toHaveKeys(['events', 'calendarView']);

    $entryForSelected = collect($result['calendarView'])
        ->firstWhere('date', $date);

    expect($entryForSelected)
        ->toBeArray()
        ->and($entryForSelected['isCurrentMonth'] ?? null)->toBeTrue()
        ->and($entryForSelected['isSelected'] ?? null)->toBeTrue()
        ->and($entryForSelected['isToday'] ?? null)->toBeTrue();
});

it('includes empty day entries with events key for month calendar and sets flags for event day using service (timezone aware)', function (): void {
    // Set application now to UTC, but test conversion by using Europe/Kyiv for building
    Carbon::setTestNow(Carbon::create(2025, 2, 10, 9, 0, 0, 'UTC'));
    $tz = 'Europe/Kyiv';

    /** @var User $user */
    $user = User::factory()->create();

    // Create an event for today in UTC (will be converted in resource/service as needed)
    $startUtc = Carbon::create(2025, 2, 10, 10, 0, 0, 'UTC');
    $endUtc = (clone $startUtc)->addHour();

    /** @var Event $event */
    $event = Event::query()->create([
        'title'           => 'Test Event',
        'status'          => 'confirmed',
        'start_date_time' => $startUtc,
        'end_date_time'   => $endUtc,
        'duration'        => $endUtc->diffInSeconds($startUtc),
        'date'            => $startUtc->format('Y-m-d'),
        'type'            => 'individual',
    ]);

    $user->events()->attach($event->getKey());

    $result = new GetMonthEvents($user, '2025-02-10', $tz)->execute();

    expect($result)->toHaveKeys(['calendarView', 'hasEventsBefore', 'hasEventsAfter']);

    $calendarView = $result['calendarView'];
    expect($calendarView)->toBeArray()->not->toBeEmpty();

    // Find the entry for the event date
    $eventEntry = collect($calendarView)->firstWhere('date', '2025-02-10');

    expect($eventEntry)
        ->toBeArray()
        ->and($eventEntry['events'] ?? null)->toBeArray()->not->toBeEmpty()
        ->and($eventEntry['isCurrentMonth'] ?? null)->toBeTrue()
        ->and($eventEntry['isSelected'] ?? null)->toBeTrue()
        ->and($eventEntry['isToday'] ?? null)->toBeTrue();

    // Ensure a day without events includes empty events array
    $emptyDay = collect($calendarView)
        ->first(fn (array $day): bool => $day['date'] !== '2025-02-10' && ($day['events'] ?? null) === []);

    expect($emptyDay)
        ->toBeArray()
        ->and($emptyDay['events'])->toBeArray()->toBe([]);
});

it('builds daily calendar grouped by month and appends days, marking flags correctly via service', function (): void {
    Carbon::setTestNow(Carbon::create(2025, 3, 5, 8, 0, 0, 'UTC'));
    $tz = 'UTC';

    /** @var User $user */
    $user = User::factory()->create();

    // Create an event on the selected day so hasEvent can be asserted
    $start = Carbon::create(2025, 3, 5, 14, 0, 0, 'UTC');
    $end = (clone $start)->addMinutes(90);

    /** @var Event $event */
    $event = Event::query()->create([
        'title'           => 'Daily Event',
        'status'          => 'confirmed',
        'start_date_time' => $start,
        'end_date_time'   => $end,
        'duration'        => $end->diffInSeconds($start),
        'date'            => $start->format('Y-m-d'),
        'type'            => 'group',
    ]);

    $user->events()->attach($event->getKey());

    $result = new GetDailyEvents($user, '2025-03-05', $tz)->execute();

    expect($result)->toHaveKeys(['events', 'calendarView']);

    $ym = '2025-03';
    $calendar = $result['calendarView'];

    expect($calendar)->toHaveKey($ym);
    $days = $calendar[$ym];

    // Ensure multiple days present to cover both set and append branches
    expect($days)->toBeArray()->and(count($days))->toBeGreaterThan(10);

    $selected = collect($days)->firstWhere('date', '2025-03-05');
    expect($selected)
        ->toBeArray()
        ->and($selected['isCurrentMonth'] ?? null)->toBeTrue()
        ->and($selected['isSelected'] ?? null)->toBeTrue()
        ->and($selected['isToday'] ?? null)->toBeTrue()
        ->and($selected['hasEvent'] ?? null)->toBeTrue();
});
