<?php

declare(strict_types=1);

use App\Enums\CalendarEventStatusEnum;
use App\Enums\RoleEnum;
use App\Models\CalendarEvent;
use App\Models\MentorProgram;
use App\Models\User;
use App\Services\Calendar\DailyCalendarEventsService;
use App\Services\Calendar\MonthCalendarEventsService;
use App\Services\Calendar\WeeklyCalendarEventsService;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Date;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
    $this->user = User::factory()->create();
    $this->user->assignRole(Role::findByName(RoleEnum::MENTOR->value));

    $this->mentorProgram = MentorProgram::factory()->create(
        ['mentor_id' => $this->user]
    );

});

it('sets flags in week formatted calendar (isCurrentMonth, isSelected, isToday) using service', function (): void {
    // Freeze time to ensure deterministic behaviour
    Date::setTestNow(Date::create(2025, 1, 15, 12, 0, 0));
    $tz = config('app.timezone');

    $date = Date::parse('2025-01-15'); // Wednesday

    $result = new WeeklyCalendarEventsService($this->user, $date, $tz)->getWeeklyCalendarEvents();

    expect($result)
        ->toHaveKeys(['calendarEvents', 'calendarView']);

    $entryForSelected = collect($result['calendarView'])
        ->firstWhere('date', $date->format('Y-m-d'));

    expect($entryForSelected)
        ->toBeArray()
        ->and($entryForSelected['isCurrentMonth'] ?? null)->toBeTrue()
        ->and($entryForSelected['isSelected'] ?? null)->toBeTrue()
        ->and($entryForSelected['isToday'] ?? null)->toBeTrue();
});

it('includes empty day entries with events key for month calendar
and sets flags for event day using service (timezone aware)', function (): void {
    // Set application now to UTC, but test conversion by using Europe/Kyiv for building
    Date::setTestNow(Date::create(2025, 2, 10, 9, 0, 0));
    $tz = 'Europe/Kyiv';

    /** @var User $user */
    $user = User::factory()->create();

    // Create an event for today in UTC (will be converted in resource/service as needed)
    $startUtc = Date::create(2025, 2, 10, 10, 0, 0);
    $endUtc = (clone $startUtc)->addHour();

    $event = CalendarEvent::query()->create([
        'title'             => 'Test Event',
        'status'            => CalendarEventStatusEnum::CONFIRMED->value,
        'start_date_time'   => $startUtc,
        'end_date_time'     => $endUtc,
        'date'              => $startUtc?->format('Y-m-d'),
        'type'              => 'individual',
        'mentor_program_id' => $this->mentorProgram->getKey(),
    ]);

    $user->calendarEvents()->attach($event->getKey());

    $date = Date::parse('2025-02-10');
    $result = new MonthCalendarEventsService($user, $date, $tz)->getMonthCalendarEvents();

    expect($result)->toHaveKeys(['calendarView', 'hasEventsBefore', 'hasEventsAfter']);

    $calendarView = $result['calendarView'];
    expect($calendarView)->toBeArray()->not->toBeEmpty();

    // Find the entry for the event date
    $eventEntry = collect($calendarView)->firstWhere('date', '2025-02-10');

    expect($eventEntry)
        ->toBeArray()
        ->and($eventEntry['calendarEvents'] ?? null)->toBeArray()->not->toBeEmpty()
        ->and($eventEntry['isCurrentMonth'] ?? null)->toBeTrue()
        ->and($eventEntry['isSelected'] ?? null)->toBeTrue()
        ->and($eventEntry['isToday'] ?? null)->toBeTrue();

    // Ensure a day without events includes empty events array
    $emptyDay = collect($calendarView)
        ->first(fn (array $day): bool => $day['date'] !== '2025-02-10' && ($day['calendarEvents'] ?? null) === []);

    expect($emptyDay)
        ->toBeArray()
        ->and($emptyDay['calendarEvents'])->toBeArray()->toBe([]);
});

it('builds daily calendar grouped by month and appends days, marking flags correctly via service', function (): void {
    Date::setTestNow(Date::create(2025, 3, 5, 8, 0, 0));
    $tz = config('app.timezone');

    // Create an event on the selected day so hasEvent can be asserted
    $start = Date::create(2025, 3, 5, 14, 0, 0);
    $end = (clone $start)->addMinutes(90);

    $event = CalendarEvent::query()->create([
        'title'             => 'Daily Event',
        'status'            => 'confirmed',
        'start_date_time'   => $start,
        'end_date_time'     => $end,
        'date'              => $start?->format('Y-m-d'),
        'type'              => 'group',
        'mentor_program_id' => $this->mentorProgram->getKey(),
    ]);

    $this->user->calendarEvents()->attach($event->getKey());
    $date = Date::parse('2025-03-05');
    $result = new DailyCalendarEventsService($this->user, $date, $tz)->getDailyCalendarEvents();

    expect($result)->toHaveKeys(['calendarEvents', 'calendarView']);

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
