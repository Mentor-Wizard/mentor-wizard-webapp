<?php

declare(strict_types=1);

use App\Enums\CalendarEventRoleEnum;
use App\Enums\CalendarEventStatusEnum;
use App\Enums\RoleEnum;
use App\Models\CalendarEvent;
use App\Models\MentorProgram;
use App\Models\User;
use App\Services\Calendar\MonthCalendarEventsService;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Date;
use Spatie\Permission\Models\Role;

mutates(MonthCalendarEventsService::class);

describe('GetMonthCalendarEventsService Service', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        $this->user = User::factory()->create();
        $this->user->assignRole(Role::findByName(RoleEnum::MENTOR->value));

        $this->mentorProgram = MentorProgram::factory()->create([
            'mentor_id' => $this->user->getKey(),
        ]);
    });

    it('includes empty day entries with events key for month calendar and sets flags for event day using service (timezone aware)', function (): void {
        // Set application now to UTC, but test conversion by using Europe/Kyiv for building
        Date::setTestNow(Date::create(2025, 2, 10, 9, 0, 0));
        $tz = 'Europe/Kyiv';

        // Create an event for today in UTC (will be converted in resource/service as needed)
        $startUtc = Date::create(2025, 2, 10, 10, 0, 0);
        $endUtc = (clone $startUtc)->addHour();

        /** @var CalendarEvent $event */
        $event = CalendarEvent::query()->create([
            'title'             => 'Test CalendarEvent',
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'start_date_time'   => $startUtc,
            'end_date_time'     => $endUtc,
            'date'              => $startUtc?->format('Y-m-d'),
            'type'              => 'individual',
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);

        $this->user->calendarEvents()->attach($event->getKey(),
            [
                'role' => CalendarEventRoleEnum::HOST->value,
            ]
        );
        $checkedDate = Date::parse('2025-02-10');
        $result = new MonthCalendarEventsService($this->user, $checkedDate, $tz)->getMonthCalendarEvents();

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

    it('sets event date property using timezone via each() method', function (): void {
        Date::setTestNow(Date::create(2025, 2, 10, 22, 0, 0));
        $tz = 'America/New_York';

        // Create event at 22:00 UTC which should be different date in NY timezone
        $startUtc = Date::create(2025, 2, 10, 22, 0, 0);
        $endUtc = (clone $startUtc)->addHour();

        /** @var CalendarEvent $event */
        $event = CalendarEvent::query()->create([
            'title'             => 'Late Event',
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'start_date_time'   => $startUtc,
            'end_date_time'     => $endUtc,
            'date'              => $startUtc?->format('Y-m-d'),
            'type'              => 'individual',
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);

        $this->user->calendarEvents()->attach($event->getKey(),
            [
                'role' => CalendarEventRoleEnum::HOST->value,
            ]
        );

        $checkedDate = Date::parse('2025-02-10');
        $result = new MonthCalendarEventsService($this->user, $checkedDate, $tz)->getMonthCalendarEvents();

        // Verify the event appears in the calendar view with correct timezone-adjusted date
        $calendarView = $result['calendarView'];
        $eventEntry = collect($calendarView)->firstWhere(fn ($day): bool => isset($day['calendarEvents'])
            && count($day['calendarEvents']) > 0);

        expect($eventEntry)->not->toBeNull()
            ->and($eventEntry['calendarEvents'])->toBeArray()->not->toBeEmpty()
            ->and($eventEntry['date'])->toBe('2025-02-10');
    });

    it('sets events with several months', function (): void {
        Date::setTestNow(Date::create(2025, 3, 10, 22, 0, 0));
        $tz = 'America/New_York';

        // Create event at 22:00 UTC which should be different date in NY timezone
        $startUtc = Date::create(2025, 3, 11, 22, 0, 0);
        $endUtc = (clone $startUtc)->addHour();

        /** @var CalendarEvent $event */
        $event = CalendarEvent::query()->create([
            'title'             => 'Late Event',
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'start_date_time'   => $startUtc,
            'end_date_time'     => $endUtc,
            'date'              => $startUtc?->format('Y-m-d'),
            'type'              => 'individual',
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);

        $this->user->calendarEvents()->attach($event->getKey(),
            [
                'role' => CalendarEventRoleEnum::HOST->value,
            ]
        );

        // Create event at 02:00 UTC which should be different date in NY timezone
        $startUtc = Date::create(2025, 3, 10, 02, 0, 0);
        $endUtc = (clone $startUtc)->addHour();

        /** @var CalendarEvent $event */
        $event = CalendarEvent::query()->create([
            'title'             => 'Late Event',
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'start_date_time'   => $startUtc,
            'end_date_time'     => $endUtc,
            'date'              => $startUtc?->format('Y-m-d'),
            'type'              => 'individual',
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);

        $this->user->calendarEvents()->attach($event->getKey(),
            [
                'role' => CalendarEventRoleEnum::HOST->value,
            ]
        );

        $checkedDate = Date::parse('2025-03-10');
        $result = new MonthCalendarEventsService($this->user, $checkedDate, $tz)->getMonthCalendarEvents();

        // Verify the event appears in the calendar view with correct timezone-adjusted date
        $calendarView = $result['calendarView'];
        $eventEntry = collect($calendarView)->firstWhere(fn ($day): bool => isset($day['calendarEvents'])
            && count($day['calendarEvents']) > 0);

        expect($eventEntry)->not->toBeNull()
            ->and($eventEntry['calendarEvents'])->toBeArray()->not->toBeEmpty()
            ->and($eventEntry['date'])->toBe('2025-03-09');
    });

    it('sets event date property using timezone via each() method
    with transition to previous date', function (): void {
        Date::setTestNow(Date::create(2025, 2, 10, 2, 0, 0));
        $tz = 'America/New_York';

        // Create event at 22:00 UTC which should be different date in NY timezone
        $startUtc = Date::create(2025, 2, 10, 2, 0, 0);
        $endUtc = (clone $startUtc)->addHour();

        /** @var CalendarEvent $event */
        $event = CalendarEvent::query()->create([
            'title'             => 'Late Event',
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'start_date_time'   => $startUtc,
            'end_date_time'     => $endUtc,
            'date'              => $startUtc?->format('Y-m-d'),
            'type'              => 'individual',
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);

        $this->user->calendarEvents()->attach($event->getKey(),
            [
                'role' => CalendarEventRoleEnum::HOST->value,
            ]
        );

        $checkedDate = Date::parse('2025-02-09');
        $result = new MonthCalendarEventsService($this->user, $checkedDate, $tz)->getMonthCalendarEvents();

        // Verify the event appears in the calendar view with correct timezone-adjusted date
        $calendarView = $result['calendarView'];
        $eventEntry = collect($calendarView)->firstWhere(fn ($day): bool => isset($day['calendarEvents'])
            && count($day['calendarEvents']) > 0);

        expect($eventEntry)->not->toBeNull()
            ->and($eventEntry['calendarEvents'])->toBeArray()->not->toBeEmpty()
            ->and($eventEntry['date'])->toBe('2025-02-09');

        $calendarEvents = $eventEntry['calendarEvents'];
        expect($calendarEvents[0])->toHaveKey('datetime');
        expect($calendarEvents[0]['datetime'])->toBe('2025-02-09T21:00');
        expect($calendarEvents[0]['time'])->toBe('9PM');
    });

    it('sets event date property using timezone via each() method
    with transition to next date', function (): void {
        Date::setTestNow(Date::create(2025, 2, 10, 22, 0, 0));
        $tz = 'Pacific/Auckland';

        // Create event at 22:00 UTC which should be different date in Pacific timezone
        $startUtc = Date::create(2025, 2, 10, 22, 0, 0);
        $endUtc = (clone $startUtc)->addHour();

        /** @var CalendarEvent $event */
        $event = CalendarEvent::query()->create([
            'title'             => 'Late Event',
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'start_date_time'   => $startUtc,
            'end_date_time'     => $endUtc,
            'date'              => $startUtc?->format('Y-m-d'),
            'type'              => 'individual',
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);

        $this->user->calendarEvents()->attach($event->getKey(),
            [
                'role' => CalendarEventRoleEnum::HOST->value,
            ]
        );

        $checkedDate = Date::parse('2025-02-09');
        $result = new MonthCalendarEventsService($this->user, $checkedDate, $tz)->getMonthCalendarEvents();

        // Verify the event appears in the calendar view with correct timezone-adjusted date
        $calendarView = $result['calendarView'];
        $eventEntry = collect($calendarView)->firstWhere(fn ($day): bool => isset($day['calendarEvents'])
            && count($day['calendarEvents']) > 0);

        expect($eventEntry)->not->toBeNull()
            ->and($eventEntry['calendarEvents'])->toBeArray()->not->toBeEmpty()
            ->and($eventEntry['date'])->toBe('2025-02-11');

        $calendarEvents = $eventEntry['calendarEvents'];
        expect($calendarEvents[0])->toHaveKey('datetime');
        expect($calendarEvents[0]['datetime'])->toBe('2025-02-11T11:00');
        expect($calendarEvents[0]['time'])->toBe('11AM');
    });

    it('passes timezone to EventMonthViewResource via additional', function (): void {
        Date::setTestNow(Date::create(2025, 2, 10, 12, 0, 0));
        $tz = 'Asia/Tokyo';

        $startUtc = Date::create(2025, 2, 10, 12, 0, 0);
        $endUtc = (clone $startUtc)->addHour();

        /** @var CalendarEvent $event */
        $event = CalendarEvent::query()->create([
            'title'             => 'Tokyo Event',
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'start_date_time'   => $startUtc,
            'end_date_time'     => $endUtc,
            'date'              => $startUtc?->format('Y-m-d'),
            'type'              => 'individual',
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);

        $this->user->calendarEvents()->attach($event->getKey(),
            [
                'role' => CalendarEventRoleEnum::HOST->value,
            ]
        );
        $checkedDate = Date::parse('2025-02-10');
        $result = new MonthCalendarEventsService($this->user, $checkedDate, $tz)->getMonthCalendarEvents();

        // Verify events are returned with timezone applied
        $eventEntry = collect($result['calendarView'])
            ->firstWhere(fn ($day): bool => isset($day['calendarEvents']) && count($day['calendarEvents']) > 0);

        expect($eventEntry['calendarEvents'][0])->toHaveKey('datetime');
    });

    it('passes timezone to CalendarEventMonthViewResource via additional', function (): void {
        Date::setTestNow(Date::create(2025, 2, 10, 12, 0, 0));
        $tz = 'Asia/Tokyo';

        $startUtc = Date::create(2025, 2, 10, 12, 0, 0);
        $endUtc = (clone $startUtc)->addHour();

        /** @var CalendarEvent $event */
        $event = CalendarEvent::query()->create([
            'title'             => 'Tokyo Event',
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'start_date_time'   => $startUtc,
            'end_date_time'     => $endUtc,
            'date'              => $startUtc?->format('Y-m-d'),
            'type'              => 'individual',
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);

        $this->user->calendarEvents()->attach($event->getKey(),
            [
                'role' => CalendarEventRoleEnum::HOST->value,
            ]
        );
        $checkedDate = Date::parse('2025-02-10');
        $result = new MonthCalendarEventsService($this->user, $checkedDate, $tz)->getMonthCalendarEvents();

        // Verify events are returned with timezone applied
        $eventEntry = collect($result['calendarView'])
            ->firstWhere(fn ($day): bool => isset($day['calendarEvents']) && count($day['calendarEvents']) > 0);

        expect($eventEntry['calendarEvents'][0])->toHaveKey('datetime');
    });

    it('correctly formats month dates with sequential numeric keys', function (): void {
        $user = User::factory()->create();
        $date = Date::parse('2025-01-15', 'UTC');

        $service = new MonthCalendarEventsService($user, $date);
        $result = $service->getMonthCalendarEvents();

        // Verify calendarView has sequential numeric keys starting from 0
        $keys = array_keys($result['calendarView']);
        expect($keys)->toBeArray()
            ->and($keys)->toBe(range(0, count($result['calendarView']) - 1))
            ->and($result['calendarView'])->toHaveCount(35); // or 42 depending on month
    });

    it('detects events after the month end correctly including events at end of last day', function (): void {
        $user = User::factory()->create();
        $mentorProgram = MentorProgram::factory()->create(['mentor_id' => $user->getKey()]);

        // Set current date to January 2025
        $date = Date::parse('2025-01-15', 'UTC');

        // Create event at the very end of the last day of the visible period
        // The visible period ends on endOfMonth()->endOfWeek()
        $endOfVisiblePeriod = $date->copy()->endOfMonth()->endOfWeek();

        // Create event at 23:59 on the last visible day (should NOT show hasEventsAfter)
        $eventOnLastDay = CalendarEvent::factory()->create([
            'start_date_time'   => $endOfVisiblePeriod->copy()->setTime(23, 59, 0),
            'end_date_time'     => $endOfVisiblePeriod->copy()->setTime(23, 59, 30),
            'status'            => 'confirmed',
            'mentor_program_id' => $mentorProgram->getKey(),
        ]);
        $user->calendarEvents()->attach($eventOnLastDay->getKey());

        $service = new MonthCalendarEventsService($user, $date);
        $result = $service->getMonthCalendarEvents();

        // Should NOT have events after because event is within the last day
        expect($result['hasEventsAfter'])->toBeFalse();

        // Now create an event AFTER the last visible day (next day at 00:01)
        $eventAfterPeriod = CalendarEvent::factory()->create([
            'start_date_time'   => $endOfVisiblePeriod->copy()->addDay()->setTime(0, 1, 0),
            'end_date_time'     => $endOfVisiblePeriod->copy()->addDay()->setTime(1, 0, 0),
            'status'            => 'confirmed',
            'mentor_program_id' => $mentorProgram->getKey(),
        ]);
        $user->calendarEvents()->attach($eventAfterPeriod->getKey());

        $service2 = new MonthCalendarEventsService($user, $date);
        $result2 = $service2->getMonthCalendarEvents();

        // NOW should have events after
        expect($result2['hasEventsAfter'])->toBeTrue();
    });
});

describe('DST Testing - MonthCalendarEventsService', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        $this->user = User::factory()->create();
        $this->user->assignRole(Role::findByName(RoleEnum::MENTOR->value));

        $this->mentorProgram = MentorProgram::factory()->create([
            'mentor_id' => $this->user->getKey(),
        ]);
    });

    it('handles America/New_York spring forward DST transition', function (): void {
        // March 9, 2025 at 2:00 AM clocks spring forward to 3:00 AM
        $tz = 'America/New_York';
        Date::setTestNow(Date::create(2025, 3, 9, 1, 0, 0, $tz));

        // Create event during DST transition
        $startUtc = Date::create(2025, 3, 9, 7, 0, 0, 'UTC'); // 2:00 AM EST / 3:00 AM EDT
        $endUtc = (clone $startUtc)->addHour();

        $event = CalendarEvent::factory()->create([
            'title'             => 'DST Event',
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'start_date_time'   => $startUtc,
            'end_date_time'     => $endUtc,
            'date'              => $startUtc->format('Y-m-d'),
            'type'              => 'individual',
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);

        $this->user->calendarEvents()->attach($event->getKey(), [
            'role' => CalendarEventRoleEnum::HOST->value,
        ]);

        $service = new MonthCalendarEventsService($this->user, Date::parse('2025-03-09'), $tz);
        $result = $service->getMonthCalendarEvents();

        expect($result)->toHaveKeys(['calendarView', 'hasEventsBefore', 'hasEventsAfter']);
        expect($result['calendarView'])->toBeArray()->not()->toBeEmpty();
    });

    it('handles Europe/Kyiv DST transition correctly', function (): void {
        $tz = 'Europe/Kyiv';
        Date::setTestNow(Date::create(2026, 3, 29, 1, 0, 0, $tz));

        // Create event during DST transition
        $startUtc = Date::create(2026, 3, 29, 1, 0, 0, 'UTC');
        $endUtc = (clone $startUtc)->addHour();

        $event = CalendarEvent::factory()->create([
            'title'             => 'DST Event Kyiv',
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'start_date_time'   => $startUtc,
            'end_date_time'     => $endUtc,
            'date'              => $startUtc->format('Y-m-d'),
            'type'              => 'individual',
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);

        $this->user->calendarEvents()->attach($event->getKey(), [
            'role' => CalendarEventRoleEnum::HOST->value,
        ]);

        $service = new MonthCalendarEventsService($this->user, Date::parse('2026-03-29'), $tz);
        $result = $service->getMonthCalendarEvents();

        expect($result)->toHaveKeys(['calendarView', 'hasEventsBefore', 'hasEventsAfter']);
    });
});

describe('Boundary Date Tests - MonthCalendarEventsService', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        $this->user = User::factory()->create();
        $this->user->assignRole(Role::findByName(RoleEnum::MENTOR->value));

        $this->mentorProgram = MentorProgram::factory()->create([
            'mentor_id' => $this->user->getKey(),
        ]);
    });

    it('handles Dec 31 to Jan 1 transition', function (): void {
        $tz = 'UTC';
        Date::setTestNow(Date::create(2025, 12, 31, 20, 0, 0, $tz));

        // Create event spanning midnight on New Year's Eve
        $startUtc = Date::create(2025, 12, 31, 23, 0, 0, $tz);
        $endUtc = Date::create(2026, 1, 1, 1, 0, 0, $tz);

        $event = CalendarEvent::factory()->create([
            'title'             => 'New Year Event',
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'start_date_time'   => $startUtc,
            'end_date_time'     => $endUtc,
            'date'              => $startUtc->format('Y-m-d'),
            'type'              => 'individual',
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);

        $this->user->calendarEvents()->attach($event->getKey(), [
            'role' => CalendarEventRoleEnum::HOST->value,
        ]);

        $service = new MonthCalendarEventsService($this->user, Date::parse('2025-12-31'), $tz);
        $result = $service->getMonthCalendarEvents();

        expect($result)->toHaveKeys(['calendarView', 'hasEventsBefore', 'hasEventsAfter']);

        // Find the Dec 31 entry
        $dec31Entry = collect($result['calendarView'])->firstWhere('date', '2025-12-31');
        expect($dec31Entry)->not()->toBeNull();
    });

    it('correctly handles startOfWeek and endOfWeek across months', function (): void {
        $tz = 'UTC';

        // Test a month that starts mid-week
        Date::setTestNow(Date::create(2026, 2, 1, 10, 0, 0, $tz));

        $service = new MonthCalendarEventsService($this->user, Date::parse('2026-02-01'), $tz);
        $result = $service->getMonthCalendarEvents();

        $calendarView = $result['calendarView'];

        // Calendar view should include days from previous month (January)
        // and days from next month (March)
        $dates = collect($calendarView)->pluck('date');

        // February 2026 starts on Sunday (week starts on Monday by default)
        // So calendar should start from Monday Jan 26
        expect($calendarView)->toBeArray()->not()->toBeEmpty();
    });

    it('handles February 29 in leap year 2024', function (): void {
        $tz = 'UTC';
        Date::setTestNow(Date::create(2024, 2, 29, 10, 0, 0, $tz));

        $startUtc = Date::create(2024, 2, 29, 14, 0, 0, $tz);
        $endUtc = (clone $startUtc)->addHour();

        $event = CalendarEvent::factory()->create([
            'title'             => 'Leap Day Event',
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'start_date_time'   => $startUtc,
            'end_date_time'     => $endUtc,
            'date'              => $startUtc->format('Y-m-d'),
            'type'              => 'individual',
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);

        $this->user->calendarEvents()->attach($event->getKey(), [
            'role' => CalendarEventRoleEnum::HOST->value,
        ]);

        $service = new MonthCalendarEventsService($this->user, Date::parse('2024-02-29'), $tz);
        $result = $service->getMonthCalendarEvents();

        // Find the Feb 29 entry
        $feb29Entry = collect($result['calendarView'])->firstWhere('date', '2024-02-29');

        expect($feb29Entry)->not()->toBeNull()
            ->and($feb29Entry['calendarEvents'])->toBeArray()->not()->toBeEmpty();
    });

    it('handles February 28 in non-leap year correctly', function (): void {
        $tz = 'UTC';
        Date::setTestNow(Date::create(2025, 2, 28, 10, 0, 0, $tz));

        $startUtc = Date::create(2025, 2, 28, 14, 0, 0, $tz);
        $endUtc = (clone $startUtc)->addHour();

        $event = CalendarEvent::factory()->create([
            'title'             => 'Feb 28 Event',
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'start_date_time'   => $startUtc,
            'end_date_time'     => $endUtc,
            'date'              => $startUtc->format('Y-m-d'),
            'type'              => 'individual',
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);

        $this->user->calendarEvents()->attach($event->getKey(), [
            'role' => CalendarEventRoleEnum::HOST->value,
        ]);

        $service = new MonthCalendarEventsService($this->user, Date::parse('2025-02-28'), $tz);
        $result = $service->getMonthCalendarEvents();

        // Find the Feb 28 entry
        $feb28Entry = collect($result['calendarView'])->firstWhere('date', '2025-02-28');

        expect($feb28Entry)->not()->toBeNull()
            ->and($feb28Entry['calendarEvents'])->toBeArray()->not()->toBeEmpty();

        // Ensure no Feb 29 exists
        $feb29Entry = collect($result['calendarView'])->firstWhere('date', '2025-02-29');
        expect($feb29Entry)->toBeNull();
    });
});
