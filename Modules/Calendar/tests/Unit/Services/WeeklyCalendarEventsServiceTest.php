<?php

declare(strict_types=1);

use App\Enums\RoleEnum;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Date;
use Modules\Calendar\Enums\CalendarEventStatusEnum;
use Modules\Calendar\Models\CalendarEvent;
use Modules\Calendar\Services\WeeklyCalendarEventsService;
use Modules\MentorProgram\Models\MentorProgram;
use Spatie\Permission\Models\Role;

mutates(WeeklyCalendarEventsService::class);

describe('GetWeeklyCalendarEventsService Service', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        $this->user = User::factory()->create();
        $this->user->assignRole(Role::findByName(RoleEnum::MENTOR->value));

        $this->mentorProgram = MentorProgram::factory()->create([
            'mentor_id' => $this->user->getKey(),
        ]);
    });

    it('sets flags in week formatted calendar (isCurrentMonth, isSelected, isToday) using service', function (): void {
        // Freeze time to ensure deterministic behaviour
        Date::setTestNow(Date::create(2025, 1, 15, 12, 0, 0));
        $tz = config('app.timezone');

        $date = '2025-01-15'; // Wednesday
        $checkedDate = Date::parse($date);
        $result = new WeeklyCalendarEventsService($this->user, $checkedDate, $tz)->getWeeklyCalendarEvents();

        expect($result)
            ->toHaveKeys(['calendarEvents', 'calendarView']);

        $entryForSelected = collect($result['calendarView'])
            ->firstWhere('date', $date);

        expect($entryForSelected)
            ->toBeArray()
            ->and($entryForSelected['isCurrentMonth'] ?? null)->toBeTrue()
            ->and($entryForSelected['isSelected'] ?? null)->toBeTrue()
            ->and($entryForSelected['isToday'] ?? null)->toBeTrue();
    });

    it('returns weekly events when events exist', function (): void {

        $firstEvent = CalendarEvent::factory()->create([
            'start_date_time'   => '2025-01-15 10:00:00',
            'end_date_time'     => '2025-01-15 11:00:00',
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);
        $this->user->calendarEvents()->attach($firstEvent->getKey());
        $secondEvent = CalendarEvent::factory()->create([
            'start_date_time'   => '2025-01-16 14:00:00',
            'end_date_time'     => '2025-01-16 15:00:00',
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);

        $this->user->calendarEvents()->attach($secondEvent->getKey());
        $service = new WeeklyCalendarEventsService(
            $this->user,
            Date::parse('2025-01-15'),
            'UTC'
        );

        $result = $service->getWeeklyCalendarEvents();

        expect($result)->not->toBeEmpty()
            ->and(count($result))->toBeGreaterThan(0);

        $flatEvents = collect($result)->flatten(1);
        expect($flatEvents)->toHaveCount(9);
    });

    it('returns empty structure when no events exist', function (): void {
        $service = new WeeklyCalendarEventsService(
            $this->user,
            Date::parse('2025-01-15'),
            'UTC'
        );

        $result = $service->getWeeklyCalendarEvents();

        expect($result)->toBeArray();
    });

    it('sets event date property using timezone via each() method and only marks correct dates with hasEvent', function (): void {
        Date::setTestNow(Date::create(2025, 1, 15, 12, 0, 0));
        // Use timezone that shifts the date
        $tz = 'America/Los_Angeles'; // UTC-8

        // Create event at 01:00 UTC on Jan 14
        // In LA timezone (UTC-8), this is 17:00 (5pm) on Jan 13
        $start = Date::create(2025, 1, 14, 1, 0, 0);
        $end = (clone $start)->addHour();

        $event = CalendarEvent::query()->create([
            'title'             => 'Early Morning UTC Event',
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'start_date_time'   => $start,
            'end_date_time'     => $end,
            'date'              => $start?->format('Y-m-d'), // This is 2025-01-14 in UTC
            'type'              => 'individual',
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);

        $this->user->calendarEvents()->attach($event->getKey());
        $checkedDate = Date::parse('2025-01-15');
        $result = new WeeklyCalendarEventsService($this->user, $checkedDate, $tz)->getWeeklyCalendarEvents();

        $calendar = $result['calendarView'];

        // Without each() setting the date property with timezone,
        // hasEvent would be on wrong date (2025-01-14 instead of 2025-01-13)
        $hasEventDays = collect($calendar)->filter(fn ($day): bool => isset($day['hasEvent']) && $day['hasEvent'])->pluck('date')->toArray();

        // Should be on Jan 13 in LA timezone, not Jan 14
        expect($hasEventDays)->toBe(['2025-01-13']);

        // Verify Jan 14 does NOT have hasEvent
        $jan14 = collect($calendar)->firstWhere('date', '2025-01-14');
        expect($jan14['hasEvent'] ?? false)->toBeFalse();
    });

    it('returns calendarView with sequential numeric keys via array_values', function (): void {
        Date::setTestNow(Date::create(2025, 1, 15, 12, 0, 0));
        $tz = config('app.timezone');

        $checkedDate = Date::parse('2025-01-15');
        $result = new WeeklyCalendarEventsService($this->user, $checkedDate, $tz)->getWeeklyCalendarEvents();

        $calendarView = $result['calendarView'];

        // Verify array has sequential numeric keys (0, 1, 2, ...)
        expect($calendarView)->toBeArray()
            ->and(array_keys($calendarView))->toBe([0, 1, 2, 3, 4, 5, 6])
            ->and(count($calendarView))->toBe(7);

        // Each entry should be an array with date key
        foreach ($calendarView as $day) {
            expect($day)->toBeArray()
                ->and($day)->toHaveKey('date');
        }
    });
});
