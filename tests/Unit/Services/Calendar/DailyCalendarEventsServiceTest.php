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
use Illuminate\Support\Facades\Date;
use Spatie\Permission\Models\Role;

mutates(DailyCalendarEventsService::class);

describe('GetDailyCalendarEventsService Service', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        $this->user = User::factory()->create();
        $this->user->assignRole(Role::findByName(RoleEnum::MENTOR->value));

        $this->mentorProgram = MentorProgram::factory()->create([
            'mentor_id' => $this->user->getKey(),
        ]);
    });

    afterEach(function (): void {
        Date::setTestNow();
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
                'status'            => CalendarEventStatusEnum::CONFIRMED->value,
                'start_date_time'   => $start,
                'end_date_time'     => $end,
                'date'              => $start?->format('Y-m-d'),
                'type'              => 'group',
                'mentor_program_id' => $this->mentorProgram->getKey(),
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
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'start_date_time'   => $start,
            'end_date_time'     => $end,
            'date'              => $start?->format('Y-m-d'),
            'type'              => 'group',
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);

        // Create an event on the selected day so hasEvent can be asserted
        $start = Date::create(2025, 4, 1, 14, 0, 0);
        $end = (clone $start)->addMinutes(90);

        $this->user->calendarEvents()->attach($event->getKey());

        $event2 = CalendarEvent::query()->create([
            'title'             => 'Daily CalendarEvent two months later',
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'start_date_time'   => $start,
            'end_date_time'     => $end,
            'date'              => $start?->format('Y-m-d'),
            'type'              => 'group',
            'mentor_program_id' => $this->mentorProgram->getKey(),
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
        expect($calendar)->toBeArray()->and(count($calendar))->toBeGreaterThanOrEqual(2);
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
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'start_date_time'   => $start,
            'end_date_time'     => $end,
            'date'              => $start?->format('Y-m-d'),
            'type'              => 'group',
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);

        // Create an event on the selected day so hasEvent can be asserted
        $start = Date::create(2025, 2, 20, 14, 0, 0);
        $end = (clone $start)->addMinutes(90);

        $this->user->calendarEvents()->attach($event->getKey());

        $event2 = CalendarEvent::query()->create([
            'title'             => 'Daily CalendarEvent two months later',
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'start_date_time'   => $start,
            'end_date_time'     => $end,
            'date'              => $start?->format('Y-m-d'),
            'type'              => 'group',
            'mentor_program_id' => $this->mentorProgram->getKey(),
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
        expect($calendar)->toBeArray()->and(count($calendar))->toBeGreaterThanOrEqual(2);
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

        $nextMonthEvent = CalendarEvent::query()->create([
            'title'             => 'Daily CalendarEvent',
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'start_date_time'   => $start,
            'end_date_time'     => $end,
            'date'              => $start?->format('Y-m-d'),
            'type'              => 'group',
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);

        $this->user->calendarEvents()->attach($nextMonthEvent->getKey());

        // Create an event on the selected day so hasEvent can be asserted
        $start = Date::create(2025, 2, 20, 14, 0, 0);
        $end = (clone $start)->addMinutes(90);

        $priorMonthEvent = CalendarEvent::query()->create([
            'title'             => 'Daily CalendarEvent',
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'start_date_time'   => $start,
            'end_date_time'     => $end,
            'date'              => $start?->format('Y-m-d'),
            'type'              => 'group',
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);
        $this->user->calendarEvents()->attach($priorMonthEvent->getKey());

        $checkedDate = Date::parse('2025-03-05');
        $result = new DailyCalendarEventsService($this->user, $checkedDate, $tz)->getDailyCalendarEvents();

        expect($result)->toHaveKeys(['calendarEvents', 'calendarView']);

        $calendar = $result['calendarView'];
        //        expect($calendar)->toHaveKey('2025-04');

        // Ensure multiple days present to cover both set and append branches
        expect($calendar)->toBeArray()->and(count($calendar))->toBeGreaterThanOrEqual(2);
        expect($calendar)->toHaveKeys(['2025-02', '2025-03']);
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
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'start_date_time'   => $start,
            'end_date_time'     => $end,
            'date'              => $start?->format('Y-m-d'),
            'type'              => 'group',
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);
        $this->user->calendarEvents()->attach($event->getKey());

        $checkedDate = Date::parse('2025-03-05');
        $result = new DailyCalendarEventsService($this->user, $checkedDate, $tz)->getDailyCalendarEvents();

        expect($result)->toHaveKeys(['calendarEvents', 'calendarView']);

        $calendar = $result['calendarView'];

        // Ensure multiple days present to cover both set and append branches
        expect($calendar)->toBeArray()->and(count($calendar))->toBe(2);
        expect($calendar)->toHaveKeys(['2025-02', '2025-03']);
    });

    it('uses first event date for start calendar month when events exist', function (): void {
        // Ensure service query window (Date::now()) includes March 2025
        Date::setTestNow(Date::create(2025, 3, 1, 12, 0, 0));
        // Create event in March, but request for January
        $calendarEvent = CalendarEvent::factory()->create([
            'start_date_time'   => '2025-03-15 10:00:00',
            'end_date_time'     => '2025-03-15 11:00:00',
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);
        $this->user->calendarEvents()->attach($calendarEvent->getKey());

        $service = new DailyCalendarEventsService($this->user, Date::parse('2025-01-10'), 'UTC');
        $result = $service->getDailyCalendarEvents();

        // Since events exist, the start calendar month should align with the first event's month (March)
        expect($result['calendarView'])->toHaveKey('2025-03');
    });

    it('uses latest event date for end calendar month when events exist', function (): void {
        // Fix the reference "now" so the service query window includes March & April events
        Date::setTestNow(Date::create(2025, 3, 15, 12, 0, 0));

        // Request for January, but latest event in April should extend calendar to April
        $marchEvent = CalendarEvent::factory()->create([
            'start_date_time'   => '2025-03-10 09:00:00',
            'end_date_time'     => '2025-03-10 10:00:00',
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);
        $aprilEvent = CalendarEvent::factory()->create([
            'start_date_time'   => '2025-04-05 09:00:00',
            'end_date_time'     => '2025-04-05 10:00:00',
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);
        $this->user->calendarEvents()->attach($marchEvent->getKey());
        $this->user->calendarEvents()->attach($aprilEvent->getKey());

        $service = new DailyCalendarEventsService($this->user, Date::parse('2025-01-10'), 'UTC');
        $result = $service->getDailyCalendarEvents();

        expect($result['calendarView'])
            ->toHaveKey('2025-03')
            ->and($result['calendarView'])
            ->toHaveKey('2025-04');
    });

    it('adds formatted date property to each event', function (): void {
        Date::setTestNow(Date::create(2025, 1, 5, 8, 0, 0));
        $calendarEvent = CalendarEvent::factory()->create([
            'start_date_time'   => '2025-01-15 14:30:00',
            'end_date_time'     => '2025-01-15 15:30:00',
            'status'            => CalendarEventStatusEnum::CONFIRMED,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);
        $this->user->calendarEvents()->attach($calendarEvent->getKey(),
            [
                'role' => CalendarEventRoleEnum::HOST->value,
            ]
        );

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
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'start_date_time'   => $start,
            'end_date_time'     => $end,
            'date'              => $start?->format('Y-m-d'),
            'type'              => 'individual',
            'mentor_program_id' => $this->mentorProgram->getKey(),
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
                'status'            => CalendarEventStatusEnum::CONFIRMED->value,
                'start_date_time'   => $start,
                'end_date_time'     => $end,
                'date'              => $start?->format('Y-m-d'),
                'type'              => 'individual',
                'mentor_program_id' => $this->mentorProgram->getKey(),
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
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'start_date_time'   => $start,
            'end_date_time'     => $end,
            'date'              => $start?->format('Y-m-d'),
            'type'              => 'individual',
            'mentor_program_id' => $this->mentorProgram->getKey(),
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
        Date::setTestNow(Date::create(2025, 1, 13, 23, 0, 0));
        $event = CalendarEvent::factory()->create([
            'title'             => 'Test Event',
            'start_date_time'   => Date::parse('2025-01-15 10:00:00', 'UTC'),
            'end_date_time'     => Date::parse('2025-01-15 11:00:00', 'UTC'),
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'description'       => 'Test description',
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);
        $this->user->calendarEvents()->attach($event->getKey(), [
            'colour'    => CalendarEventColoursEnum::RED->value,
            'role'      => CalendarEventRoleEnum::HOST->value,
        ]);

        $service = new DailyCalendarEventsService(
            $this->user,
            Date::parse('2025-01-15'),
            'UTC'
        );

        $result = $service->getDailyCalendarEvents();

        expect($result['calendarEvents'][0])->toHaveKey('colour')
            ->and($result['calendarEvents'][0]['colour'])->toBe(CalendarEventColoursEnum::RED->value)
            ->and($result['calendarEvents'])->toHaveKeys([0])
            ->and(array_keys($result['calendarEvents'][0]))->toBe(['id', 'time', 'dateTime',
                'durationIndex', 'startIndex', 'title', 'webLink', 'colour']);
    });

    it('uses default values when fields are null', function (): void {
        Date::setTestNow(Date::create(2025, 1, 5, 8, 0, 0));
        $event = CalendarEvent::factory()->create([
            'start_date_time'   => Date::parse('2025-01-15 10:00:00', 'UTC'),
            'end_date_time'     => Date::parse('2025-01-15 11:00:00', 'UTC'),
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'description'       => null,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);

        $this->user->calendarEvents()->attach($event->getKey(),
            [
                'role' => CalendarEventRoleEnum::HOST->value,
            ]
        );

        $service = new DailyCalendarEventsService(
            $this->user,
            Date::parse('2025-01-15'),
            'UTC'
        );

        $result = $service->getDailyCalendarEvents();

        expect($result['calendarEvents'][0]['colour'])->not->toBeNull();
    });

    it('filters events correctly based on condition', function (): void {
        Date::setTestNow(Date::create(2025, 1, 5, 8, 0, 0));
        $event1 = CalendarEvent::factory()->create([
            'start_date_time'   => Date::parse('2025-01-15 10:00:00', 'UTC'),
            'end_date_time'     => Date::parse('2025-01-15 11:00:00', 'UTC'),
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);

        $this->user->calendarEvents()->attach($event1->getKey(),
            [
                'role' => CalendarEventRoleEnum::HOST->value,
            ]
        );

        $event2 = CalendarEvent::factory()->create([
            'start_date_time'   => Date::parse('2024-01-15 14:00:00', 'UTC'),
            'end_date_time'     => Date::parse('2024-01-15 15:00:00', 'UTC'),
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);

        $this->user->calendarEvents()->attach($event2->getKey(),
            [
                'role' => CalendarEventRoleEnum::HOST->value,
            ]
        );

        $service = new DailyCalendarEventsService(
            $this->user,
            Date::parse('2025-01-15'),
            'UTC'
        );

        $result = $service->getDailyCalendarEvents();
        expect($result['calendarEvents'])->toHaveCount(1);
    });

});
