<?php

declare(strict_types=1);

use App\Enums\RoleEnum;
use App\Models\CalendarEvent;
use App\Models\MentorProgram;
use App\Models\User;
use App\Services\Calendar\MonthCalendarEventsService;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Date;

mutates(MonthCalendarEventsService::class);

describe('GetMonthCalendarEventsService Service', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        $this->user = User::factory()->create();
        $this->user->assignRole(RoleEnum::MENTOR->value);

        $this->mentorProgram = MentorProgram::factory()->create([
            'mentor_id' => $this->user->id,
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
            'status'            => 'confirmed',
            'start_date_time'   => $startUtc,
            'end_date_time'     => $endUtc,
            'date'              => $startUtc?->format('Y-m-d'),
            'type'              => 'individual',
            'mentor_program_id' => $this->mentorProgram->id,
        ]);

        $this->user->calendarEvents()->attach($event->getKey());
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
            'status'            => 'confirmed',
            'start_date_time'   => $startUtc,
            'end_date_time'     => $endUtc,
            'date'              => $startUtc?->format('Y-m-d'),
            'type'              => 'individual',
            'mentor_program_id' => $this->mentorProgram->id,
        ]);

        $this->user->calendarEvents()->attach($event->getKey());

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
            'status'            => 'confirmed',
            'start_date_time'   => $startUtc,
            'end_date_time'     => $endUtc,
            'date'              => $startUtc?->format('Y-m-d'),
            'type'              => 'individual',
            'mentor_program_id' => $this->mentorProgram->id,
        ]);

        $this->user->calendarEvents()->attach($event->getKey());

        // Create event at 02:00 UTC which should be different date in NY timezone
        $startUtc = Date::create(2025, 3, 10, 02, 0, 0);
        $endUtc = (clone $startUtc)->addHour();

        /** @var CalendarEvent $event */
        $event = CalendarEvent::query()->create([
            'title'             => 'Late Event',
            'status'            => 'confirmed',
            'start_date_time'   => $startUtc,
            'end_date_time'     => $endUtc,
            'date'              => $startUtc?->format('Y-m-d'),
            'type'              => 'individual',
            'mentor_program_id' => $this->mentorProgram->id,
        ]);

        $this->user->calendarEvents()->attach($event->getKey());

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
            'status'            => 'confirmed',
            'start_date_time'   => $startUtc,
            'end_date_time'     => $endUtc,
            'date'              => $startUtc?->format('Y-m-d'),
            'type'              => 'individual',
            'mentor_program_id' => $this->mentorProgram->id,
        ]);

        $this->user->calendarEvents()->attach($event->getKey());

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
            'status'            => 'confirmed',
            'start_date_time'   => $startUtc,
            'end_date_time'     => $endUtc,
            'date'              => $startUtc?->format('Y-m-d'),
            'type'              => 'individual',
            'mentor_program_id' => $this->mentorProgram->id,
        ]);

        $this->user->calendarEvents()->attach($event->getKey());

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
            'status'            => 'confirmed',
            'start_date_time'   => $startUtc,
            'end_date_time'     => $endUtc,
            'date'              => $startUtc?->format('Y-m-d'),
            'type'              => 'individual',
            'mentor_program_id' => $this->mentorProgram->id,
        ]);

        $this->user->calendarEvents()->attach($event->getKey());
        $checkedDate = Date::parse('2025-02-10');
        $result = new MonthCalendarEventsService($this->user, $checkedDate, $tz)->getMonthCalendarEvents();

        // Verify events are returned with timezone applied
        $eventEntry = collect($result['calendarView'])->firstWhere(fn ($day): bool => isset($day['calendarEvents']) && count($day['calendarEvents']) > 0);

        expect($eventEntry['calendarEvents'][0])->toHaveKey('datetime');
    });

    it('groups events by timezone-adjusted date when events cross midnight boundary', function (): void {
        Date::setTestNow(Date::create(2025, 2, 10, 9, 0, 0));
        $tz = 'America/New_York'; // UTC-5

        // Create event at 23:00 UTC on Feb 10, which is 18:00 (6PM) Feb 10 in NY
        $event1StartUtc = Date::create(2025, 2, 10, 23, 0, 0);
        $event1 = CalendarEvent::query()->create([
            'title'              => 'Late Evening Event',
            'status'             => 'confirmed',
            'start_date_time'    => $event1StartUtc,
            'end_date_time'      => $event1StartUtc->copy()->addHour(),
            'date'               => $event1StartUtc->format('Y-m-d'), // Will be 2025-02-10 in UTC
            'type'               => 'individual',
            'mentor_program_id'  => $this->mentorProgram->getKey(),
        ]);

        // Create event at 02:00 UTC on Feb 11, which is 21:00 (9PM) Feb 10 in NY (same day!)
        $event2StartUtc = Date::create(2025, 2, 11, 2, 0, 0);
        $event2 = CalendarEvent::query()->create([
            'title'              => 'Early Morning Event',
            'status'             => 'confirmed',
            'start_date_time'    => $event2StartUtc,
            'end_date_time'      => $event2StartUtc->copy()->addHour(),
            'date'               => $event2StartUtc->format('Y-m-d'), // Will be 2025-02-11 in UTC
            'type'               => 'individual',
            'mentor_program_id'  => $this->mentorProgram->getKey(),
        ]);

        $this->user->calendarEvents()->attach([$event1->getKey(), $event2->getKey()]);

        $checkedDate = Date::parse('2025-02-10');
        $result = new MonthCalendarEventsService($this->user, $checkedDate, $tz)->getMonthCalendarEvents();

        $calendarView = $result['calendarView'];

        // Both events should appear on Feb 10 when viewed in NY timezone
        $feb10Entry = collect($calendarView)->firstWhere('date', '2025-02-10');

        expect($feb10Entry)->not->toBeNull()
            ->and($feb10Entry['calendarEvents'])->toBeArray()->toHaveCount(2)
            ->and($feb10Entry['calendarEvents'][0]['name'])->toBe('Late Evening Event')
            ->and($feb10Entry['calendarEvents'][1]['name'])->toBe('Early Morning Event');

        // Feb 11 should have no events in NY timezone
        $feb11Entry = collect($calendarView)->firstWhere('date', '2025-02-11');
        expect($feb11Entry['calendarEvents'] ?? [])->toBeEmpty();
    });
});
