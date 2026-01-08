<?php

declare(strict_types=1);

use App\Enums\CalendarEventColoursEnum;
use App\Enums\CalendarEventRoleEnum;
use App\Enums\CalendarEventStatusEnum;
use App\Enums\RoleEnum;
use App\Models\CalendarEvent;
use App\Models\MentorProgram;
use App\Models\User;
use App\Services\Calendar\DailyCalendarEventsService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;

uses(RefreshDatabase::class);

mutates(DailyCalendarEventsService::class);

describe('GetDailyCalendarEventsService Service', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        $this->user = User::factory()->create();
        $this->user->assignRole(RoleEnum::MENTOR->value);

        $this->mentorProgram = MentorProgram::factory()->create([
            'mentor_id' => $this->user->id,
        ]
        );
    });

    afterEach(function (): void {
        Date::setTestNow(); // Reset to real time
    });

    it('builds daily calendar grouped by month and appends days, marking flags correctly via service',
        function (): void {
            Date::setTestNow(Date::create(2025, 3, 5, 8, 0, 0));
            $tz = 'UTC';

            // Create an event on the selected day so hasEvent can be asserted
            $start = Date::create(2025, 3, 5, 14, 0, 0);
            $end = (clone $start)->addMinutes(90);

            /** @var CalendarEvent $event */
            $event = CalendarEvent::query()->create([
                'title'             => 'Daily CalendarEvent',
                'status'            => 'confirmed',
                'start_date_time'   => $start,
                'end_date_time'     => $end,
                'date'              => $start?->format('Y-m-d'),
                'type'              => 'group',
                'mentor_program_id' => $this->mentorProgram->id,
            ]);

            $this->user->calendarEvents()->attach($event->getKey());
            $checkedDate = Date::parse('2025-03-05');
            $result = new DailyCalendarEventsService($this->user, $checkedDate, $tz)->getDailyCalendarEvents();

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

    it('test empty event variant',
        function (): void {
            Date::setTestNow(Date::create(2025, 3, 5, 8, 0, 0));
            $tz = 'UTC';

            $checkedDate = Date::parse('2025-03-08');
            $result = new DailyCalendarEventsService($this->user, $checkedDate, $tz)->getDailyCalendarEvents();

            expect($result)->toHaveKeys(['calendarEvents', 'calendarView']);

            $ym = '2025-03';
            $calendar = $result['calendarView'];

            expect($calendar)->toHaveKey($ym);
            $days = $calendar[$ym];

            $calendarEvents = $result['calendarEvents'];
            expect($calendarEvents)->toBeEmpty();

            // Ensure multiple days present to cover both set and append branches
            expect($days)->toBeArray()->and(count($days))->toBeGreaterThan(10);

            $selected = collect($days)->firstWhere('date', '2025-03-08');
            expect($selected)
                ->toBeArray()
                ->and($selected['isCurrentMonth'] ?? null)->toBeTrue()
                ->and($selected['isSelected'] ?? null)->toBeTrue()
                ->and($selected['isToday'] ?? null)->toBeNull()
                ->and($selected['hasEvent'] ?? null)->toBeNull();
        });

    it('builds daily calendar without any events, marking flags correctly via service', function (): void {
        Date::setTestNow(Date::create(2025, 3, 5, 8, 0, 0));
        $tz = 'UTC';

        // Create an event on the selected day so hasEvent can be asserted
        $start = Date::create(2025, 3, 5, 14, 0, 0);
        $end = (clone $start)->addMinutes(90);

        $checkedDate = Date::parse('2025-03-05');
        $result = new DailyCalendarEventsService($this->user, $checkedDate, $tz)->getDailyCalendarEvents();

        expect($result)->toHaveKeys(['calendarEvents', 'calendarView']);

        $ym = '2025-03';
        $calendar = $result['calendarView'];

        expect($calendar)->toHaveKey($ym);
        $days = $calendar[$ym];

        // Ensure multiple days present to cover both set and append branches
        expect($days)->toBeArray()->and(count($days))->toBe(42);

        $selected = collect($days)->firstWhere('date', '2025-03-05');
        expect($selected)
            ->toBeArray()
            ->and($selected['isCurrentMonth'] ?? null)->toBeTrue()
            ->and($selected['isSelected'] ?? null)->toBeTrue()
            ->and($selected['isToday'] ?? null)->toBeTrue()
            ->and($selected['hasEvent'] ?? null)->toBeNull();

    });

    it('builds daily calendar grouped by month and appends days,
     covering several months marking flags correctly via service', function (): void {
        Date::setTestNow(Date::create(2025, 3, 5, 8, 0, 0));
        $tz = 'UTC';

        // Create an event on the selected day so hasEvent can be asserted
        $start = Date::create(2025, 3, 5, 14, 0, 0);
        $end = (clone $start)->addMinutes(90);

        /** @var CalendarEvent $event */
        $event = CalendarEvent::query()->create([
            'title'             => 'Daily CalendarEvent',
            'status'            => 'confirmed',
            'start_date_time'   => $start,
            'end_date_time'     => $end,
            'date'              => $start?->format('Y-m-d'),
            'type'              => 'group',
            'mentor_program_id' => $this->mentorProgram->id,
        ]);

        // Create an event on the selected day so hasEvent can be asserted
        $start = Date::create(2025, 4, 1, 14, 0, 0);
        $end = (clone $start)->addMinutes(90);

        $this->user->calendarEvents()->attach($event->getKey());

        $event2 = CalendarEvent::query()->create([
            'title'             => 'Daily CalendarEvent two months later',
            'status'            => 'confirmed',
            'start_date_time'   => $start,
            'end_date_time'     => $end,
            'date'              => $start?->format('Y-m-d'),
            'type'              => 'group',
            'mentor_program_id' => $this->mentorProgram->id,
        ]);
        $this->user->calendarEvents()->attach($event2->getKey());

        $checkedDate = Date::parse('2025-03-05');
        $result = new DailyCalendarEventsService($this->user, $checkedDate, $tz)->getDailyCalendarEvents();

        expect($result)->toHaveKeys(['calendarEvents', 'calendarView']);

        $ym = '2025-03';
        $calendar = $result['calendarView'];

        expect($calendar)->toHaveKey($ym);
        $days = $calendar[$ym];

        // Ensure multiple days present to cover both set and append branches
        expect($calendar)->toBeArray()->and(count($calendar))->toBe(2);
        expect($days)->toBeArray()->and(count($days))->toBe(42);

        $selected = collect($days)->firstWhere('date', '2025-03-05');
        expect($selected)
            ->toBeArray()
            ->and($selected['isCurrentMonth'] ?? null)->toBeTrue()
            ->and($selected['isSelected'] ?? null)->toBeTrue()
            ->and($selected['isToday'] ?? null)->toBeTrue()
            ->and($selected['hasEvent'] ?? null)->toBeTrue()
            ->and($selected['date'] ?? null)->toBe('2025-03-05');
    });

    it('builds daily calendar grouped by month and appends days,
     covering several months before, marking flags correctly via service', function (): void {
        Date::setTestNow(Date::create(2025, 3, 5, 8, 0, 0));
        $tz = 'UTC';

        // Create an event on the selected day so hasEvent can be asserted
        $start = Date::create(2025, 3, 5, 14, 0, 0);
        $end = (clone $start)->addMinutes(90);

        /** @var CalendarEvent $event */
        $event = CalendarEvent::query()->create([
            'title'             => 'Daily CalendarEvent',
            'status'            => 'confirmed',
            'start_date_time'   => $start,
            'end_date_time'     => $end,
            'date'              => $start?->format('Y-m-d'),
            'type'              => 'group',
            'mentor_program_id' => $this->mentorProgram->id,
        ]);

        // Create an event on the selected day so hasEvent can be asserted
        $start = Date::create(2025, 2, 20, 14, 0, 0);
        $end = (clone $start)->addMinutes(90);

        $this->user->calendarEvents()->attach($event->getKey());

        $event2 = CalendarEvent::query()->create([
            'title'           => 'Daily CalendarEvent two months later',
            'status'          => 'confirmed',
            'start_date_time' => $start,
            'end_date_time'   => $end,
            'date'            => $start?->format('Y-m-d'),
            'type'            => 'group',
        ]);
        $this->user->calendarEvents()->attach($event2->getKey());

        $checkedDate = Date::parse('2025-03-05');
        $result = new DailyCalendarEventsService($this->user, $checkedDate, $tz)->getDailyCalendarEvents();

        expect($result)->toHaveKeys(['calendarEvents', 'calendarView']);

        $ym = '2025-03';
        $calendar = $result['calendarView'];

        expect($calendar)->toHaveKey($ym);
        $days = $calendar[$ym];

        // Ensure multiple days present to cover both set and append branches
        expect($calendar)->toBeArray()->and(count($calendar))->toBe(2);
        expect($days)->toBeArray()->and(count($days))->toBe(42);

        $selected = collect($days)->firstWhere('date', '2025-03-05');
        expect($selected)
            ->toBeArray()
            ->and($selected['isCurrentMonth'] ?? null)->toBeTrue()
            ->and($selected['isSelected'] ?? null)->toBeTrue()
            ->and($selected['isToday'] ?? null)->toBeTrue()
            ->and($selected['hasEvent'] ?? null)->toBeTrue()
            ->and($selected['date'] ?? null)->toBe('2025-03-05');
    });

    it('builds daily calendar grouped by month and checking number of months,
     marking flags correctly via service', function (): void {
        Date::setTestNow(Date::create(2025, 3, 5, 8, 0, 0));
        $tz = 'UTC';

        // Create an event on the selected day so hasEvent can be asserted
        $start = Date::create(2025, 4, 5, 14, 0, 0);
        $end = (clone $start)->addMinutes(90);

        /** @var CalendarEvent $event */
        $event = CalendarEvent::query()->create([
            'title'             => 'Daily CalendarEvent',
            'status'            => 'confirmed',
            'start_date_time'   => $start,
            'end_date_time'     => $end,
            'date'              => $start?->format('Y-m-d'),
            'type'              => 'group',
            'mentor_program_id' => $this->mentorProgram->id,
        ]);

        // Create an event on the selected day so hasEvent can be asserted
        $start = Date::create(2025, 2, 20, 14, 0, 0);
        $end = (clone $start)->addMinutes(90);

        $this->user->calendarEvents()->attach($event->getKey());

        $event2 = CalendarEvent::query()->create([
            'title'             => 'Daily CalendarEvent',
            'status'            => 'confirmed',
            'start_date_time'   => $start,
            'end_date_time'     => $end,
            'date'              => $start?->format('Y-m-d'),
            'type'              => 'group',
            'mentor_program_id' => $this->mentorProgram->id,
        ]);
        $this->user->calendarEvents()->attach($event2->getKey());

        $checkedDate = Date::parse('2025-03-05');
        $result = new DailyCalendarEventsService($this->user, $checkedDate, $tz)->getDailyCalendarEvents();

        expect($result)->toHaveKeys(['calendarEvents', 'calendarView']);

        $calendar = $result['calendarView'];

        expect($calendar)->toHaveKey('2025-02');
        expect($calendar)->toHaveKey('2025-03');

        // Ensure multiple days present to cover both set and append branches
        expect($calendar)->toBeArray()->and(count($calendar))->toBe(3);
    });

    it('builds daily calendar checking start time mutation', function (): void {
        Date::setTestNow(Date::create(2025, 3, 5, 8, 0, 0));
        $tz = 'UTC';

        // Create an event on the selected day so hasEvent can be asserted
        $start = Date::create(2025, 2, 10, 14, 0, 0);
        $end = (clone $start)->addMinutes(90);

        /** @var CalendarEvent $event */
        $event = CalendarEvent::query()->create([
            'title'             => 'Daily CalendarEvent',
            'status'            => 'confirmed',
            'start_date_time'   => $start,
            'end_date_time'     => $end,
            'date'              => $start?->format('Y-m-d'),
            'type'              => 'group',
            'mentor_program_id' => $this->mentorProgram->id,
        ]);
        $this->user->calendarEvents()->attach($event->getKey());

        $checkedDate = Date::parse('2025-03-05');
        $result = new DailyCalendarEventsService($this->user, $checkedDate, $tz)->getDailyCalendarEvents();

        expect($result)->toHaveKeys(['calendarEvents', 'calendarView']);

        $calendar = $result['calendarView'];

        expect($calendar)->toHaveKey('2025-02');
        expect($calendar)->toHaveKey('2025-03');

        // Ensure multiple days present to cover both set and append branches
        expect($calendar)->toBeArray()->and(count($calendar))->toBe(2);
    });

    it('uses first event date for start calendar month when events exist', function (): void {
        // Create event in March, but request for January
        $calendarEvent = CalendarEvent::factory()->create([
            'start_date_time'   => '2025-03-15 10:00:00',
            'end_date_time'     => '2025-03-15 11:00:00',
            'mentor_program_id' => $this->mentorProgram->id,
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
        ]);
        $this->user->calendarEvents()->attach($calendarEvent->getKey());

        $service = new DailyCalendarEventsService($this->user, Date::parse('2025-01-10'), 'UTC');
        $result = $service->getDailyCalendarEvents();

        expect($result['calendarView'])->toHaveKey('2025-01');
    });

    it('adds formatted date property to each event', function (): void {

        $calendarEvent = CalendarEvent::factory()->create([
            'start_date_time'   => '2025-01-15 14:30:00',
            'end_date_time'     => '2025-01-15 15:30:00',
            'mentor_program_id' => $this->mentorProgram->id,
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
        ]);
        $this->user->calendarEvents()->attach($calendarEvent->getKey());

        $service = new DailyCalendarEventsService($this->user, Date::parse('2025-01-15'), 'UTC');
        $result = $service->getDailyCalendarEvents();

        expect($result['calendarEvents'][0]['dateTime'])->toBe('2025-01-15"UTC"14:30:00');
    });

    it('sets event date property using timezone via each() method', function (): void {
        Date::setTestNow(Date::create(2025, 3, 5, 23, 0, 0));
        $tz = 'Pacific/Auckland';

        // Create event at 23:00 UTC which should be next day in Auckland
        $start = Date::create(2025, 3, 5, 23, 0, 0);
        $end = (clone $start)->addHour();

        /** @var CalendarEvent $event */
        $event = CalendarEvent::query()->create([
            'title'             => 'Late Event',
            'start_date_time'   => $start,
            'end_date_time'     => $end,
            'date'              => $start?->format('Y-m-d'),
            'type'              => 'individual',
            'mentor_program_id' => $this->mentorProgram->id,
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
        ]);

        $this->user->calendarEvents()->attach($event->getKey());
        $checkedDate = Date::parse('2025-03-05');
        $result = new DailyCalendarEventsService($this->user, $checkedDate, $tz)->getDailyCalendarEvents();

        // Verify calendarView has proper structure with hasEvent flags
        $calendar = $result['calendarView'];

        expect($calendar)->toBeArray();

        // Find a day with hasEvent flag to verify each() worked
        $hasEventDay = collect($calendar)->flatten(1)->firstWhere('hasEvent', true);
        expect($hasEventDay)->not->toBeNull()
            ->and($hasEventDay['hasEvent'])->toBeTrue();
    });

    it('sets event date property using timezone via each() method with backward transition',
        function (): void {
            Date::setTestNow(Date::create(2025, 3, 5, 23, 0, 0));
            $tz = 'Pacific/Auckland';

            // Create event at 23:00 UTC which should be next day in Auckland
            $start = Date::create(2025, 3, 5, 22, 0, 0);
            $end = (clone $start)->addHour();

            /** @var CalendarEvent $event */
            $event = CalendarEvent::query()->create([
                'title'             => 'Late Event',
                'start_date_time'   => $start,
                'end_date_time'     => $end,
                'date'              => $start?->format('Y-m-d'),
                'type'              => 'individual',
                'mentor_program_id' => $this->mentorProgram->id,
                'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            ]);

            $this->user->calendarEvents()->attach($event->getKey());
            $checkedDate = Date::parse('2025-03-05');
            $result = new DailyCalendarEventsService($this->user, $checkedDate, $tz)->getDailyCalendarEvents();

            $calendar = $result['calendarView'];

            expect($calendar)->toBeArray();

            $hasEventDay = collect($calendar)->flatten(1)->firstWhere('hasEvent', true);
            expect($hasEventDay)->not->toBeNull()
                ->and($hasEventDay['hasEvent'])->toBeTrue()
                ->and($hasEventDay['date'])->toBe('2025-03-06')
                ->and($hasEventDay['isToday'])->toBeTrue();

        });

    it('sets event date property using timezone via each() method with transition', function (): void {
        Date::setTestNow(Date::create(2025, 3, 5, 23, 0, 0));
        $tz = 'Pacific/Auckland';

        // Create event at 23:00 UTC which should be next day in Auckland
        $start = Date::create(2025, 3, 5, 02, 0, 0);
        $end = (clone $start)->addHour();

        /** @var CalendarEvent $event */
        $event = CalendarEvent::query()->create([
            'title'             => 'Late Event',
            'start_date_time'   => $start,
            'end_date_time'     => $end,
            'date'              => $start?->format('Y-m-d'),
            'type'              => 'individual',
            'mentor_program_id' => $this->mentorProgram->id,
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
        ]);

        $this->user->calendarEvents()->attach($event->getKey());
        $checkedDate = Date::parse('2025-03-05');
        $result = new DailyCalendarEventsService($this->user, $checkedDate, $tz)->getDailyCalendarEvents();

        // Verify calendarView has proper structure with hasEvent flags
        $calendar = $result['calendarView'];
        expect($calendar)->toBeArray();

        // Find a day with hasEvent flag to verify each() worked
        $hasEventDay = collect($calendar)->flatten(1)->firstWhere('hasEvent', true);
        expect($hasEventDay)->not->toBeNull()
            ->and($hasEventDay['hasEvent'])->toBeTrue();
    });
    it('properly formats event data with all fields present', function (): void {
        $event = CalendarEvent::factory()->create([
            'title'             => 'Test Event',
            'start_date_time'   => Date::parse('2025-01-15 10:00:00', 'UTC'),
            'end_date_time'     => Date::parse('2025-01-15 11:00:00', 'UTC'),
            'description'       => 'Test description',
            'mentor_program_id' => $this->mentorProgram->id,
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
        ]);
        $this->user->calendarEvents()->attach($event->getKey(), [
            'colour' => CalendarEventColoursEnum::RED->value,
        ]);

        $service = new DailyCalendarEventsService(
            $this->user,
            Date::parse('2025-01-15'),
            'UTC'
        );

        $result = $service->getDailyCalendarEvents();

        expect($result['calendarEvents'][0])->toHaveKey('colour')
            ->and($result['calendarEvents'][0]['colour'])->toBe(CalendarEventColoursEnum::RED->value);

        expect($result['calendarEvents'])->toHaveKeys([0])
            ->and(array_keys($result['calendarEvents'][0]))->toBe(['id', 'time', 'dateTime',
                'durationIndex', 'startIndex', 'title', 'webLink', 'colour']);
    });

    it('uses default values when fields are null', function (): void {
        $event = CalendarEvent::factory()->create([
            'start_date_time'   => Date::parse('2025-01-15 10:00:00', 'UTC'),
            'end_date_time'     => Date::parse('2025-01-15 11:00:00', 'UTC'),
            'description'       => null,
            'mentor_program_id' => $this->mentorProgram->id,
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
        ]);

        $this->user->calendarEvents()->attach($event->getKey());

        $service = new DailyCalendarEventsService(
            $this->user,
            Date::parse('2025-01-15'),
            'UTC'
        );

        $result = $service->getDailyCalendarEvents();

        expect($result['calendarEvents'][0]['colour'])->not->toBeNull();
    });

    it('filters events correctly based on condition', function (): void {
        $this->user = User::factory()->create();

        $event1 = CalendarEvent::factory()->create([
            'start_date_time'   => Date::parse('2025-01-15 10:00:00', 'UTC'),
            'end_date_time'     => Date::parse('2025-01-15 11:00:00', 'UTC'),
            'mentor_program_id' => $this->mentorProgram->id,
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
        ]);

        $this->user->calendarEvents()->attach($event1->getKey());

        $event2 = CalendarEvent::factory()->create([
            'start_date_time'   => Date::parse('2024-01-15 14:00:00', 'UTC'),
            'end_date_time'     => Date::parse('2024-01-15 15:00:00', 'UTC'),
            'mentor_program_id' => $this->mentorProgram->id,
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
        ]);

        $this->user->calendarEvents()->attach($event2->getKey());

        $service = new DailyCalendarEventsService(
            $this->user,
            Date::parse('2025-01-15'),
            'UTC'
        );

        $result = $service->getDailyCalendarEvents();

        expect($result['calendarEvents'])->toHaveCount(1);
    });

    it('handles empty calendar when no events exist and uses date fallback for endCalendarMonth', function (): void {
        Date::setTestNow(Date::create(2025, 2, 10, 9, 0, 0));
        $tz = 'Europe/Kyiv';

        // Don't create any events - this tests the coalesce fallback for $latestEvent->start_date_time ?? $this->date
        $checkedDate = Date::parse('2025-02-10');
        $service = new DailyCalendarEventsService($this->user, $checkedDate, $tz);

        // Access the private prepareDailyDateConfiguration method that contains the mutations
        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('prepareDailyDateConfiguration');

        $result = $method->invoke($service);

        // When no events exist, months calculation should use $this->date as fallback
        expect($result)->toHaveKeys(['todayDate', 'tomorrowDate', 'months', 'daysEvents'])
            ->and($result['daysEvents'])->toBeArray()->toBeEmpty()
            ->and($result['months'])->toBeArray()->not->toBeEmpty();

        // Verify the months array includes the checked date month (Feb 2025)
        $monthDates = array_map(fn ($month) => $month->format('Y-m'), $result['months']);
        expect($monthDates)->toContain('2025-02');
    });

    it('returns daysEvents with clean numeric array keys after unique removes duplicates', function (): void {
        Date::setTestNow(Date::create(2025, 2, 10, 9, 0, 0));
        $tz = 'Europe/Kyiv';

        // Create multiple events on duplicate dates to test unique() + array_values()
        $dates = ['2025-02-10', '2025-02-10', '2025-02-15', '2025-02-15', '2025-02-20'];
        foreach ($dates as $index => $date) {
            $startUtc = Date::parse($date.' 10:00:00')->addHours($index);
            $event = CalendarEvent::query()->create([
                'title'              => 'Event '.$index.' on '.$date,
                'status'             => 'confirmed',
                'start_date_time'    => $startUtc,
                'end_date_time'      => $startUtc->copy()->addHour(),
                'date'               => $date,
                'type'               => 'individual',
                'mentor_program_id'  => $this->mentorProgram->getKey(),
            ]);
            $this->user->calendarEvents()->attach($event->getKey());
        }

        $service = new DailyCalendarEventsService($this->user, Date::parse('2025-02-10'), $tz);

        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('prepareDailyDateConfiguration');

        $result = $method->invoke($service);

        // After unique() on 5 events with 3 unique dates, array_values() ensures [0, 1, 2] keys
        expect($result['daysEvents'])
            ->toBeArray()
            ->toHaveCount(3)
            ->and(array_keys($result['daysEvents']))->toEqual([0, 1, 2]) // Clean sequential keys from array_values()
            ->and($result['daysEvents'])->toContain('2025-02-10', '2025-02-15', '2025-02-20');
    });

    it('uses latestEvent start_date_time to calculate endCalendarMonth when events span months', function (): void {
        Date::setTestNow(Date::create(2025, 2, 10, 9, 0, 0));
        $tz = 'Europe/Kyiv';

        // Create events spanning multiple months to test $latestEvent->start_date_time logic
        $firstEventDate = Date::create(2025, 2, 5, 10, 0, 0);
        $lastEventDate = Date::create(2025, 3, 5, 14, 0, 0);

        $event1 = CalendarEvent::query()->create([
            'title'              => 'First Event',
            'status'             => 'confirmed',
            'start_date_time'    => $firstEventDate,
            'end_date_time'      => $firstEventDate->copy()->addHour(),
            'date'               => $firstEventDate->format('Y-m-d'),
            'type'               => 'individual',
            'mentor_program_id'  => $this->mentorProgram->getKey(),
        ]);

        $event2 = CalendarEvent::query()->create([
            'title'              => 'Last Event',
            'status'             => 'confirmed',
            'start_date_time'    => $lastEventDate,
            'end_date_time'      => $lastEventDate->copy()->addHour(),
            'date'               => $lastEventDate->format('Y-m-d'),
            'type'               => 'individual',
            'mentor_program_id'  => $this->mentorProgram->getKey(),
        ]);

        $this->user->calendarEvents()->attach([$event1->getKey(), $event2->getKey()],
            ['role' => CalendarEventRoleEnum::HOST->value]);

        $service = new DailyCalendarEventsService($this->user, Date::parse('2025-02-10'), $tz);

        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('prepareDailyDateConfiguration');

        $result = $method->invoke($service);

        // The months array should span from Feb to April based on $latestEvent->start_date_time (April 25)
        $monthDates = array_map(fn ($month) => $month->format('Y-m'), $result['months']);

        expect($monthDates)
            ->toContain('2025-02', '2025-03')
            ->and($result['daysEvents'])->toHaveCount(2)
            ->and($result['daysEvents'])->toContain('2025-02-05', '2025-03-05');
    });

});
