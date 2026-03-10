<?php

declare(strict_types=1);

use App\Actions\Pages\Calendar\CalendarsListPage;
use App\Enums\CalendarEventColoursEnum;
use App\Enums\CalendarEventRoleEnum;
use App\Enums\CalendarEventStatusEnum;
use App\Enums\CalendarEventTypeEnum;
use App\Enums\RoleEnum;
use App\Models\CalendarEvent;
use App\Models\MentorProgram;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Date;
use Inertia\Response;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;

mutates(CalendarsListPage::class);

describe('List Calendar CalendarEvent Page', function (): void {
    beforeEach(function (): void {
        Date::setTestNow(Date::create(2026, 1, 15, 10, 0, 0, 'UTC'));

        $this->seed(RoleSeeder::class);
        $this->user = User::factory()->create();
        $this->user->assignRole(Role::findByName(RoleEnum::MENTOR->value));
        $this->user->profile->timezone = 'Europe/Kyiv';
        $this->user->profile->save();

        // Create mentor program for the logged-in mentor
        $this->mentorProgram = MentorProgram::factory()->create([
            'mentor_id' => $this->user->getKey(),
        ]);

        $this->nonMentorUser = User::factory()->create();
        actingAs($this->user);
        auth()->login($this->user);
        $this->data = [
            'title'             => 'Default event',
            'status'            => CalendarEventStatusEnum::CONFIRMED,
            'start_date_time'   => Date::today()->endOfMonth()->endOfWeek()
                ->setTime(19, 59, 0)->format('Y-m-d H:i:s'),
            'end_date_time'     => Date::today()->endOfMonth()->endOfWeek()
                ->setTime(21, 59, 0)->format('Y-m-d H:i:s'),
            'date'              => Date::today()->endOfMonth()->endOfWeek()
                ->setTime(19, 59, 0)->format('Y-m-d'),
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'web_link'          => 'https://www.google.com',
            'description'       => 'Test description',
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ];
        $this->monthEvent = CalendarEvent::factory()->create($this->data);
        $this->monthEvent->calendarEventUsers()->attach($this->user->getKey(),
            [
                'role'   => CalendarEventRoleEnum::HOST,
                'colour' => CalendarEventColoursEnum::BLUE->value,
            ]);
    });

    afterEach(function (): void {
        Date::setTestNow();
    });

    it('uses provided date from request', function (): void {
        actingAs($this->user);
        auth()->login($this->user);
        $calendarEvent1 = CalendarEvent::factory()->create([
            'start_date_time'   => Date::parse('2025-01-15')->setTime(15, 0, 0),
            'end_date_time'     => Date::parse('2025-01-15')->setTime(16, 0, 0),
            'date'              => Date::parse('2025-01-15')->format('Y-m-d'),
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);
        $calendarEvent2 = CalendarEvent::factory()->create([
            'start_date_time'   => Date::parse('2025-01-20')->setTime(15, 0, 0),
            'end_date_time'     => Date::parse('2025-01-20')->setTime(16, 0, 0),
            'date'              => Date::parse('2025-01-20')->format('Y-m-d'),
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);

        $calendarEvent1->calendarEventUsers()->attach($this->user->getKey());
        $calendarEvent2->calendarEventUsers()->attach($this->user->getKey());

        $action = new CalendarsListPage;

        $requestData = [
            'date'     => '2025-01-15',
        ];

        $request = new Request($requestData);
        $response = $action->handle($request);
        $resultData = $response->toResponse(request())->getOriginalContent();
        $result = $resultData->getData()['page'];

        expect($response)->toBeInstanceOf(Response::class)
            ->and(Arr::get($result, 'component'))->toBe('Calendar/CalendarEventsList')
            ->and(Arr::get($result, 'props.locale'))->toBe(app()->getLocale())
            ->and(Arr::get($result, 'props.calendarEvents.calendarView.16.calendarEvents'))
            ->toHaveCount(1)
            ->and(Arr::get($result, 'props.calendarEvents.calendarView.21.calendarEvents'))
            ->toHaveCount(1);

    });

    it('renders Month View of Calendar CalendarEvent list page, without events before',
        function (): void {
            actingAs($this->user);
            auth()->login($this->user);

            $action = new CalendarsListPage;

            $requestData = [
                'date'     => Date::now('Europe/Kyiv')->addDays(2)->format('Y-m-d'),
                'mode'     => 'Month view',
            ];

            $request = new Request($requestData);

            $response = $action->handle($request);
            $resultData = $response->toResponse(request())->getOriginalContent();
            $result = $resultData->getData()['page'];

            $firstDate = Date::today()->addDays(2)->startOfMonth()->startOfWeek()->format('Y-m-d');
            $lastDate = Date::today()->endOfMonth()->endOfWeek()->format('Y-m-d');
            $difference = (Date::parse($firstDate)->diffInDays($lastDate));

            expect($response)->toBeInstanceOf(Response::class)
                ->and(Arr::get($result, 'component'))->toBe('Calendar/CalendarEventsList')
                ->and(Arr::get($result, 'props.locale'))->toBe(app()->getLocale())
                ->and(Arr::get($result, 'props.permissions'))->toBe('create')
                ->and(Arr::get($result, 'props.calendarEvents.hasEventsBefore'))->toBeFalse()
                ->and(Arr::get($result, 'props.calendarEvents.hasEventsAfter'))->toBeFalse()
                ->and(Arr::get($result, 'props.calendarEvents.calendarView.'
                    .$difference.'.calendarEvents.0.name'))->toBe($this->data['title'])
                ->and(Arr::get($result, 'props.calendarEvents.calendarView.'
                    .$difference.'.calendarEvents.0.webLink'))->toBe($this->data['web_link'])
                ->and(Arr::get($result, 'props.calendarEvents.calendarView.'
                    .$difference.'.calendarEvents.0.datetime'))
                ->toBe(Date::today()->endOfMonth()->endOfWeek()
                    ->setTime(19, 59, 0)
                    ->timezone('Europe/Kyiv')->format('Y-m-d\TH:i'))
                ->and(Arr::get($result, 'props.calendarEvents.calendarView.'
                    .$difference.'.calendarEvents.0.time'))->toBe('9PM')
                ->and(Arr::get($result, 'props.calendarEvents.calendarView.'
                    .$difference.'.calendarEvents.0.id'))->toBe($this->monthEvent->getKey())
                ->and(Arr::get($result, 'props.calendarEvents.calendarView.'
                    .$difference.'.date'))->toBe($this->data['date']);
        });

    it('renders Month View of Calendar CalendarEvent list page, without events before without date',
        function (): void {
            actingAs($this->user);
            auth()->login($this->user);
            $action = new CalendarsListPage;

            $requestData = [
                'date'     => null,
                'mode'     => 'Month view',
            ];

            $request = new Request($requestData);

            $response = $action->handle($request);
            $resultData = $response->toResponse(request())->getOriginalContent();
            $result = $resultData->getData()['page'];

            $firstDate = Date::today()->addDays(2)->startOfMonth()->startOfWeek()->format('Y-m-d');
            $lastdate = Date::today()->addDays(2)->endOfMonth()->endOfWeek()->format('Y-m-d');
            $difference = (Date::parse($firstDate)->diffInDays($lastdate));

            expect($response)->toBeInstanceOf(Response::class)
                ->and(Arr::get($result, 'component'))->toBe('Calendar/CalendarEventsList')
                ->and(Arr::get($result, 'props.locale'))->toBe(app()->getLocale())
                ->and(Arr::get($result, 'props.permissions'))->toBe('create')
                ->and(Arr::get($result, 'props.calendarEvents.hasEventsBefore'))->toBeFalse()
                ->and(Arr::get($result, 'props.calendarEvents.hasEventsAfter'))->toBeFalse()
                ->and(Arr::get($result, 'props.calendarEvents.calendarView.'
                    .$difference.'.calendarEvents.0.name'))->toBe($this->data['title'])
                ->and(Arr::get($result, 'props.calendarEvents.calendarView.'
                    .$difference.'.calendarEvents.0.webLink'))->toBe($this->data['web_link'])
                ->and(Arr::get($result, 'props.calendarEvents.calendarView.'
                    .$difference.'.calendarEvents.0.datetime'))
                ->toBe(Date::today()->endOfMonth()->endOfWeek()
                    ->setTime(19, 59, 0)->timezone('Europe/Kyiv')->format('Y-m-d\TH:i'))
                ->and(Arr::get($result, 'props.calendarEvents.calendarView.'
                    .$difference.'.calendarEvents.0.time'))->toBe('9PM')
                ->and(Arr::get($result, 'props.calendarEvents.calendarView.'
                    .$difference.'.calendarEvents.0.id'))->toBe($this->monthEvent->getKey())
                ->and(Arr::get($result, 'props.calendarEvents.calendarView.'
                    .$difference.'.date'))->toBe($this->data['date']);
        });

    it('renders Month View of Calendar CalendarEvent list page,
        with events after and before with moving date at previous day during timezone transformation',
        function (): void {
            actingAs($this->user);
            auth()->login($this->user);
            $action = new CalendarsListPage;

            $previousMonthDate = Date::today('Europe/Kyiv')->startOfMonth()->subDays(10);
            $this->data['start_date_time'] = $previousMonthDate->format('Y-m-d').' 22:00:00';
            $this->data['date'] = $previousMonthDate->format('Y-m-d');

            $this->previousMonthEventMonthChecking = CalendarEvent::factory()->create($this->data);
            $this->previousMonthEventMonthChecking->calendarEventUsers()->attach($this->user->getKey(),
                ['role' => CalendarEventRoleEnum::HOST, 'colour' => CalendarEventColoursEnum::BLUE->value]);

            $nextMonthDate = Date::today()->endOfMonth()->addDays(10);
            $this->data['start_date_time'] = $nextMonthDate->format('Y-m-d').' 22:00:00';
            $this->data['date'] = $nextMonthDate->format('Y-m-d');

            $this->nextMonthEventMonthChecking = CalendarEvent::factory()->create($this->data);
            $this->nextMonthEventMonthChecking->calendarEventUsers()->attach($this->user->getKey(),
                ['role' => CalendarEventRoleEnum::HOST, 'colour' => CalendarEventColoursEnum::BLUE->value]);

            $requestData = [
                'date'     => Date::now('Europe/Kyiv')->format('Y-m-d'),
                'mode'     => 'Month view',
            ];

            $request = new Request($requestData);

            $response = $action->handle($request);
            $resultData = $response->toResponse(request())->getOriginalContent();
            $result = $resultData->getData()['page'];

            $firstDate = Date::today()->startOfMonth()->startOfWeek()->format('Y-m-d');
            $lastdate = Date::today()->endOfMonth()->endOfWeek()->format('Y-m-d');
            $difference = Date::parse($firstDate)->diffInDays(Date::parse($lastdate));

            expect($response)->toBeInstanceOf(Response::class)
                ->and(Arr::get($result, 'component'))->toBe('Calendar/CalendarEventsList')
                ->and(Arr::get($result, 'props.permissions'))->toBe('create')
                ->and(Arr::get($result, 'props.locale'))->toBe(app()->getLocale())
                ->and(Arr::get($result, 'props.calendarEvents.hasEventsBefore'))->toBeTrue()
                ->and(Arr::get($result, 'props.calendarEvents.hasEventsAfter'))->toBeTrue()
                ->and(Arr::get($result, 'props.calendarEvents.calendarView.'
                    .$difference.'.calendarEvents.0.name'))->toBe($this->data['title'])
                ->and(Arr::get($result, 'props.calendarEvents.calendarView.'
                    .$difference.'.calendarEvents.0.webLink'))->toBe($this->data['web_link'])
                ->and(Arr::get($result, 'props.calendarEvents.calendarView.'
                    .$difference.'.calendarEvents.0.datetime'))->toBe(Date::today()->endOfMonth()->endOfWeek()
                ->setTime(19, 59, 0)->timezone('Europe/Kyiv')->format('Y-m-d\TH:i'))
                ->and(Arr::get($result, 'props.calendarEvents.calendarView.'
                    .$difference.'.calendarEvents.0.time'))->toBe('9PM')
                ->and(Arr::get($result, 'props.calendarEvents.calendarView.'
                    .$difference.'.calendarEvents.0.id'))->toBe($this->monthEvent->getKey())
                ->and(Arr::get($result, 'props.calendarEvents.calendarView.'.$difference.'.date'))
                ->toBe(Date::today()->endOfMonth()->endOfWeek()->setTime(19, 59, 0)
                    ->timezone('Europe/Kyiv')->format('Y-m-d'));
        });

    it('renders Month View of Calendar CalendarEvent list page, with events after and before
        without moving date during timezone transformation', function (): void {
        actingAs($this->user);
        auth()->login($this->user);
        $action = new CalendarsListPage;

        $previousMonthDate = Date::today('Europe/Kyiv')->startOfMonth()->subDays(10);
        $this->data['start_date_time'] = $previousMonthDate->format('Y-m-d').' 15:00:00';
        $this->data['date'] = $previousMonthDate->format('Y-m-d');

        $this->previousMonthEventMonthChecking = CalendarEvent::factory()->create($this->data);
        $this->previousMonthEventMonthChecking->calendarEventUsers()->attach($this->user->getKey(),
            [
                'role'   => CalendarEventRoleEnum::HOST,
                'colour' => CalendarEventColoursEnum::BLUE->value,
            ]);

        $nextMonthDate = Date::today()->endOfMonth()->addDays(10);
        $this->data['start_date_time'] = $nextMonthDate->format('Y-m-d').' 15:00:00';
        $this->data['date'] = $nextMonthDate->format('Y-m-d');

        $this->nextMonthEventMonthChecking = CalendarEvent::factory()->create($this->data);
        $this->nextMonthEventMonthChecking->calendarEventUsers()->attach($this->user->getKey(),
            [
                'role'   => CalendarEventRoleEnum::HOST,
                'colour' => CalendarEventColoursEnum::BLUE->value,
            ]);

        $requestData = [
            'date'     => Date::now('Europe/Kyiv')->format('Y-m-d'),
            'mode'     => 'Month view',
        ];

        $request = new Request($requestData);

        $response = $action->handle($request);
        $resultData = $response->toResponse(request())->getOriginalContent();
        $result = $resultData->getData()['page'];

        $firstDate = Date::today()->startOfMonth()->startOfWeek()->format('Y-m-d');
        $lastdate = Date::today()->endOfMonth()->endOfWeek()->format('Y-m-d');
        $difference = Date::parse($firstDate)->diffInDays(Date::parse($lastdate));

        expect($response)->toBeInstanceOf(Response::class)
            ->and(Arr::get($result, 'component'))->toBe('Calendar/CalendarEventsList')
            ->and(Arr::get($result, 'props.permissions'))->toBe('create')
            ->and(Arr::get($result, 'props.locale'))->toBe(app()->getLocale())
            ->and(Arr::get($result, 'props.calendarEvents.hasEventsBefore'))->toBeTrue()
            ->and(Arr::get($result, 'props.calendarEvents.hasEventsAfter'))->toBeTrue()
            ->and(Arr::get($result, 'props.calendarEvents.calendarView.'
                .$difference.'.calendarEvents.0.name'))->toBe($this->data['title'])
            ->and(Arr::get($result, 'props.calendarEvents.calendarView.'
                .$difference.'.calendarEvents.0.webLink'))->toBe($this->data['web_link'])
            ->and(Arr::get($result, 'props.calendarEvents.calendarView.'
                .$difference.'.calendarEvents.0.datetime'))->toBe(Date::today()->endOfMonth()->endOfWeek()
            ->setTime(19, 59, 0)
            ->timezone('Europe/Kyiv')->format('Y-m-d\TH:i'))
            ->and(Arr::get($result, 'props.calendarEvents.calendarView.'
                .$difference.'.calendarEvents.0.time'))->toBe('9PM')
            ->and(Arr::get($result, 'props.calendarEvents.calendarView.'
                .$difference.'.calendarEvents.0.id'))->toBe($this->monthEvent->getKey())
            ->and(Arr::get($result, 'props.calendarEvents.calendarView.'
                .$difference.'.date'))
            ->toBe(Date::today()->endOfMonth()->endOfWeek()->setTime(19, 59, 0)
                ->timezone('Europe/Kyiv')->format('Y-m-d'));
    });

    it('renders Month View of Calendar CalendarEvent list page, with events after and before
        with moving date at next day during timezone transformation', function (): void {
        actingAs($this->user);
        auth()->login($this->user);
        $action = new CalendarsListPage;
        $this->user->profile->timezone = 'America/New_York';
        $this->user->profile->save();

        $previousMonthDate = Date::today('America/New_York')->startOfMonth()->subDays(10);
        $this->data['start_date_time'] = $previousMonthDate->format('Y-m-d').' 02:00:00';
        $this->data['end_date_time'] = $previousMonthDate->copy()->addHour()->format('Y-m-d').' 02:00:00';
        $this->data['date'] = $previousMonthDate->format('Y-m-d');

        $this->previousMonthEventMonthChecking = CalendarEvent::factory()->create($this->data);
        $this->previousMonthEventMonthChecking->calendarEventUsers()->attach($this->user->getKey(),
            [
                'role'   => CalendarEventRoleEnum::HOST,
                'colour' => CalendarEventColoursEnum::BLUE->value,
            ]);

        $nextMonthDate = Date::today()->endOfMonth()->addDays(10);
        $this->data['start_date_time'] = $nextMonthDate->format('Y-m-d').' 15:00:00';
        $this->data['end_date_time'] = $nextMonthDate->copy()->addHour()->format('Y-m-d').' 15:00:00';
        $this->data['date'] = $nextMonthDate->format('Y-m-d');

        $this->nextMonthEventMonthChecking = CalendarEvent::factory()->create($this->data);
        $this->nextMonthEventMonthChecking->calendarEventUsers()->attach($this->user->getKey(),
            [
                'role'   => CalendarEventRoleEnum::HOST,
                'colour' => CalendarEventColoursEnum::BLUE->value,
            ]);

        $requestData = [
            'date'     => Date::now('America/New_York')->format('Y-m-d'),
            'mode'     => 'Month view',
        ];

        $request = new Request($requestData);

        $response = $action->handle($request);
        $resultData = $response->toResponse(request())->getOriginalContent();
        $result = $resultData->getData()['page'];

        $firstDate = Date::today()->startOfMonth()->startOfWeek()->format('Y-m-d');
        $lastdate = Date::today()->endOfMonth()->endOfWeek()->format('Y-m-d');
        $difference = Date::parse($firstDate)->diffInDays(Date::parse($lastdate));

        expect($response)->toBeInstanceOf(Response::class)
            ->and(Arr::get($result, 'component'))->toBe('Calendar/CalendarEventsList')
            ->and(Arr::get($result, 'props.permissions'))->toBe('create')
            ->and(Arr::get($result, 'props.locale'))->toBe(app()->getLocale())
            ->and(Arr::get($result, 'props.calendarEvents.hasEventsBefore'))->toBeTrue()
            ->and(Arr::get($result, 'props.calendarEvents.hasEventsAfter'))->toBeTrue()
            ->and(Arr::get($result, 'props.calendarEvents.calendarView.'
                .$difference.'.calendarEvents.0.name'))->toBe($this->data['title'])
            ->and(Arr::get($result, 'props.calendarEvents.calendarView.'
                .$difference.'.calendarEvents.0.webLink'))->toBe($this->data['web_link'])
            ->and(Arr::get($result, 'props.calendarEvents.calendarView.'
                .$difference.'.calendarEvents.0.datetime'))->toBe(Date::today()->endOfMonth()->endOfWeek()
            ->setTime(19, 59, 0)
            ->timezone('America/New_York')->format('Y-m-d\TH:i'))
            ->and(Arr::get($result, 'props.calendarEvents.calendarView.'
                .$difference.'.calendarEvents.0.time'))->toBe('2PM')
            ->and(Arr::get($result, 'props.calendarEvents.calendarView.'
                .$difference.'.calendarEvents.0.id'))->toBe($this->monthEvent->getKey())
            ->and(Arr::get($result, 'props.calendarEvents.calendarView.'
                .$difference.'.date'))
            ->toBe(Date::today()->endOfMonth()->endOfWeek()->setTime(19, 59, 0)
                ->timezone('America/New_York')->format('Y-m-d'));
    });

    it('renders Month View of Calendar CalendarEvent list page for today date, if no date is set',
        function (): void {
            actingAs($this->user);
            auth()->login($this->user);
            $action = new CalendarsListPage;

            $previousMonthDate = Date::today('Europe/Kyiv')->startOfMonth()->subDays(10);
            $this->data['start_date_time'] = $previousMonthDate->format('Y-m-d').' 15:00:00';
            $this->data['date'] = $previousMonthDate->format('Y-m-d');

            $this->previousMonthEventMonthChecking = CalendarEvent::factory()->create($this->data);
            $this->previousMonthEventMonthChecking->calendarEventUsers()->attach($this->user->getKey(),
                [
                    'role'   => CalendarEventRoleEnum::HOST,
                    'colour' => CalendarEventColoursEnum::BLUE->value,
                ]);

            $nextMonthDate = Date::today()->endOfMonth()->addDays(10);
            $this->data['start_date_time'] = $nextMonthDate->format('Y-m-d').' 15:00:00';
            $this->data['date'] = $nextMonthDate->format('Y-m-d');

            $this->nextMonthEventMonthChecking = CalendarEvent::factory()->create($this->data);
            $this->nextMonthEventMonthChecking->calendarEventUsers()->attach($this->user->getKey(),
                [
                    'role'   => CalendarEventRoleEnum::HOST,
                    'colour' => CalendarEventColoursEnum::BLUE->value,
                ]);

            $requestData = [
                'date'     => null,
                'mode'     => 'Month view',
            ];

            $request = new Request($requestData);

            $response = $action->handle($request);
            $resultData = $response->toResponse(request())->getOriginalContent();
            $result = $resultData->getData()['page'];

            $firstDate = Date::today()->startOfMonth()->startOfWeek()->format('Y-m-d');
            $lastdate = Date::today()->endOfMonth()->endOfWeek()->format('Y-m-d');
            $difference = Date::parse($firstDate)->diffInDays(Date::parse($lastdate));

            expect($response)->toBeInstanceOf(Response::class)
                ->and(Arr::get($result, 'component'))->toBe('Calendar/CalendarEventsList')
                ->and(Arr::get($result, 'props.permissions'))->toBe('create')
                ->and(Arr::get($result, 'props.locale'))->toBe(app()->getLocale())
                ->and(Arr::get($result, 'props.availableColours'))->toBe(CalendarEventColoursEnum::values())
                ->and(Arr::get($result, 'props.calendarEvents.hasEventsBefore'))->toBeTrue()
                ->and(Arr::get($result, 'props.calendarEvents.hasEventsAfter'))->toBeTrue()
                ->and(Arr::get($result, 'props.calendarEvents.calendarView.'
                    .$difference.'.calendarEvents.0.name'))->toBe($this->data['title'])
                ->and(Arr::get($result, 'props.calendarEvents.calendarView.'
                    .$difference.'.calendarEvents.0.webLink'))->toBe($this->data['web_link'])
                ->and(Arr::get($result, 'props.calendarEvents.calendarView.'
                    .$difference.'.calendarEvents.0.datetime'))->toBe(Date::today()->endOfMonth()->endOfWeek()
                ->setTime(21, 59, 0)
                ->setTimezone('UTC')->format('Y-m-d\TH:i'))
                ->and(Arr::get($result, 'props.calendarEvents.calendarView.'
                    .$difference.'.calendarEvents.0.time'))->toBe('9PM')
                ->and(Arr::get($result, 'props.calendarEvents.calendarView.'
                    .$difference.'.calendarEvents.0.id'))->toBe($this->monthEvent->getKey())
                ->and(Arr::get($result, 'props.calendarEvents.calendarView.'
                    .$difference.'.date'))
                ->toBe(Date::today()->endOfMonth()->endOfWeek()->setTime(21, 59, 0)
                    ->setTimezone('UTC')->format('Y-m-d'));
        });

    it('renders Month View of Calendar CalendarEvent list page for today date, if no date and month are set',
        function (): void {
            actingAs($this->user);
            auth()->login($this->user);
            $action = new CalendarsListPage;
            $this->user->profile->timezone = 'Europe/Kyiv';
            $this->user->profile->save();

            $previousMonthDate = Date::today('Europe/Kyiv')->startOfMonth()->subDays(10);
            $this->data['start_date_time'] = $previousMonthDate->format('Y-m-d').' 15:00:00';
            $this->data['date'] = $previousMonthDate->format('Y-m-d');

            $this->previousMonthEventMonthChecking = CalendarEvent::factory()->create($this->data);
            $this->previousMonthEventMonthChecking->calendarEventUsers()->attach($this->user->getKey(),
                [
                    'role'   => CalendarEventRoleEnum::HOST,
                    'colour' => CalendarEventColoursEnum::BLUE->value,
                ]);

            $nextMonthDate = Date::today()->endOfMonth()->addDays(10);
            $this->data['start_date_time'] = $nextMonthDate->format('Y-m-d').' 15:00:00';
            $this->data['date'] = $nextMonthDate->format('Y-m-d');

            $this->nextMonthEventMonthChecking = CalendarEvent::factory()->create($this->data);
            $this->nextMonthEventMonthChecking->calendarEventUsers()->attach($this->user->getKey(),
                [
                    'role'   => CalendarEventRoleEnum::HOST,
                    'colour' => CalendarEventColoursEnum::BLUE->value,
                ]);

            $requestData = [
                'date'     => null,
                'mode'     => null,
            ];

            $request = new Request($requestData);

            $response = $action->handle($request);
            $resultData = $response->toResponse(request())->getOriginalContent();
            $result = $resultData->getData()['page'];

            $firstDate = Date::today()->startOfMonth()->startOfWeek()->format('Y-m-d');
            $lastdate = Date::today()->endOfMonth()->endOfWeek()->format('Y-m-d');
            $difference = Date::parse($firstDate)->diffInDays(Date::parse($lastdate));

            expect($response)->toBeInstanceOf(Response::class)
                ->and(Arr::get($result, 'component'))->toBe('Calendar/CalendarEventsList')
                ->and(Arr::get($result, 'props.permissions'))->toBe('create')
                ->and(Arr::get($result, 'props.locale'))->toBe(app()->getLocale())
                ->and(Arr::get($result, 'props.calendarEvents.hasEventsBefore'))->toBeTrue()
                ->and(Arr::get($result, 'props.calendarEvents.hasEventsAfter'))->toBeTrue()
                ->and(Arr::get($result, 'props.calendarEvents.calendarView.'
                    .$difference.'.calendarEvents.0.name'))->toBe($this->data['title'])
                ->and(Arr::get($result, 'props.calendarEvents.calendarView.'
                    .$difference.'.calendarEvents.0.webLink'))->toBe($this->data['web_link'])
                ->and(Arr::get($result, 'props.calendarEvents.calendarView.'
                    .$difference.'.calendarEvents.0.datetime'))->toBe(Date::today()->endOfMonth()->endOfWeek()
                ->setTime(21, 59, 0)->format('Y-m-d\TH:i'))
                ->and(Arr::get($result, 'props.calendarEvents.calendarView.'
                    .$difference.'.calendarEvents.0.time'))->toBe('9PM')
                ->and(Arr::get($result, 'props.calendarEvents.calendarView.'
                    .$difference.'.calendarEvents.0.id'))->toBe($this->monthEvent->getKey())
                ->and(Arr::get($result, 'props.calendarEvents.calendarView.'
                    .$difference.'.date'))
                ->toBe(Date::today()->endOfMonth()->endOfWeek()->setTime(21, 59, 0)
                    ->format('Y-m-d'));
        });

    it('renders Weekly View of Calendar CalendarEvent list page
        without moving date during timezone transformation', function (): void {
        Date::setTestNow(Date::create(2025, 12, 20, 8, 0, 0, 'UTC'));
        actingAs($this->user);
        auth()->login($this->user);
        $action = new CalendarsListPage;

        $eventDate = Date::today('Europe/Kyiv')
            ->endOfWeek()->setTime(15, 0, 0);
        $this->data['start_date_time'] = $eventDate->copy()
            ->timezone(config('app.timezone'))->format('Y-m-d H:i:s');
        $this->data['end_date_time'] = $eventDate->copy()
            ->addHour()->timezone(config('app.timezone'))->format('Y-m-d H:i:s');
        $this->data['date'] = $eventDate->format('Y-m-d');

        $this->weekEvent = CalendarEvent::factory()->create($this->data);

        $this->weekEvent->calendarEventUsers()->attach($this->user->getKey(),
            [
                'role'      => CalendarEventRoleEnum::HOST,
                'colour'    => CalendarEventColoursEnum::BLUE->value,
            ]);

        $requestDate = Date::now('Europe/Kyiv')->format('Y-m-d');
        $requestData = [
            'date'     => $requestDate,
            'mode'     => 'Week view',
        ];

        $request = new Request($requestData);

        $response = $action->handle($request);
        $resultData = $response->toResponse(request())->getOriginalContent();
        $result = $resultData->getData()['page'];

        $timezoneAbbreviation = $eventDate->format('T');
        $expectedDayNumber = (int) $eventDate->format('w') + 1;
        $weekStartDate = Date::parse($requestDate, 'Europe/Kyiv')->startOfWeek();
        $weekEndDate = Date::parse($requestDate, 'Europe/Kyiv')->endOfWeek();

        expect($response)->toBeInstanceOf(Response::class)
            ->and(Arr::get($result, 'component'))->toBe('Calendar/CalendarEventsList')
            ->and(Arr::get($result, 'props.permissions'))->toBe('create')
            ->and(Arr::get($result, 'props.locale'))->toBe(app()->getLocale())
            ->and(Arr::get($result, 'props.calendarEvents.calendarEvents.0.dayNumber'))
            ->toBe($expectedDayNumber)
            ->and(Arr::get($result, 'props.calendarEvents.calendarEvents.0.webLink'))
            ->toBe($this->data['web_link'])
            ->and(Arr::get($result, 'props.calendarEvents.calendarEvents.0.dateTime'))
            ->toBe($eventDate->format('Y-m-d').'"'
                .$timezoneAbbreviation.'"'.$eventDate->format('H:i:s'))
            ->and(Arr::get($result, 'props.calendarEvents.calendarEvents.0.time'))
            ->toBe('3:00 PM')
            ->and(Arr::get($result, 'props.calendarEvents.calendarEvents.0.startIndex'))
            ->toBe(92)
            ->and(Arr::get($result, 'props.calendarEvents.calendarEvents.0.durationIndex'))
            ->toBe(12)
            ->and(Arr::get($result, 'props.calendarEvents.calendarEvents.0.id'))
            ->toBe($this->weekEvent->getKey())
            ->and(Arr::get($result, 'props.calendarEvents.calendarEvents.0.colour'))
            ->not()->toBeNull()
            ->and(Arr::get($result, 'props.calendarEvents.calendarView'))
            ->toBeArray()
            ->and(Arr::get($result, 'props.calendarEvents.calendarView'))
            ->toHaveCount(7)
            ->and(Arr::get($result, 'props.calendarEvents.calendarView.0.date'))
            ->toBe($weekStartDate->format('Y-m-d'))
            ->and(Arr::get($result, 'props.calendarEvents.calendarView.6.date'))
            ->toBe($weekEndDate->format('Y-m-d'))
            ->and(Arr::get($result, 'props.calendarEvents.calendarView.6.hasEvent'))
            ->tobeTrue()
            ->and(Arr::get($result, 'props.calendarEvents.calendarView.6.isCurrentMonth'))
            ->tobeTrue();
    });

    it('renders Weekly View of Calendar CalendarEvent list page
        with moving date to previous day during timezone transformation', function (): void {
        Date::setTestNow(Date::create(2025, 12, 20, 8, 0, 0, 'UTC'));
        actingAs($this->user);
        auth()->login($this->user);
        $action = new CalendarsListPage;

        $eventDate = Date::today('Europe/Kyiv')
            ->endOfWeek()->setTime(22, 0, 0);
        $this->data['start_date_time'] = $eventDate->copy()
            ->timezone(config('app.timezone'))->format('Y-m-d H:i:s');
        $this->data['end_date_time'] = $eventDate->copy()->addHour()
            ->timezone(config('app.timezone'))->format('Y-m-d H:i:s');
        $this->data['date'] = $eventDate->format('Y-m-d');

        $this->weekEvent = CalendarEvent::factory()->create($this->data);

        $this->weekEvent->calendarEventUsers()->attach($this->user->getKey(),
            [
                'role'   => CalendarEventRoleEnum::HOST,
                'colour' => CalendarEventColoursEnum::BLUE->value,
            ]);

        $requestDate = Date::now('Europe/Kyiv')->format('Y-m-d');
        $requestData = [
            'date'     => $requestDate,
            'mode'     => 'Week view',
        ];

        $request = new Request($requestData);

        $response = $action->handle($request);
        $resultData = $response->toResponse(request())->getOriginalContent();
        $result = $resultData->getData()['page'];

        $timezoneAbbreviation = $eventDate->format('T');
        $expectedDayNumber = (int) $eventDate->format('w') + 1;
        $weekStartDate = Date::parse($requestDate, 'Europe/Kyiv')->startOfWeek();
        $weekEndDate = Date::parse($requestDate, 'Europe/Kyiv')->endOfWeek();

        expect($response)->toBeInstanceOf(Response::class)
            ->and(Arr::get($result, 'component'))->toBe('Calendar/CalendarEventsList')
            ->and(Arr::get($result, 'props.permissions'))->toBe('create')
            ->and(Arr::get($result, 'props.locale'))->toBe(app()->getLocale())
            ->and(Arr::get($result, 'props.calendarEvents.calendarEvents.0.dayNumber'))
            ->toBe($expectedDayNumber)
            ->and(Arr::get($result, 'props.calendarEvents.calendarEvents.0.webLink'))
            ->toBe($this->data['web_link'])
            ->and(Arr::get($result, 'props.calendarEvents.calendarEvents.0.dateTime'))
            ->toBe($eventDate->format('Y-m-d').'"'
                .$timezoneAbbreviation.'"'.$eventDate->format('H:i:s'))
            ->and(Arr::get($result, 'props.calendarEvents.calendarEvents.0.time'))
            ->toBe('10:00 PM')
            ->and(Arr::get($result, 'props.calendarEvents.calendarEvents.0.startIndex'))
            ->toBe(134)
            ->and(Arr::get($result, 'props.calendarEvents.calendarEvents.0.durationIndex'))
            ->toBe(12)
            ->and(Arr::get($result, 'props.calendarEvents.calendarEvents.0.id'))
            ->toBe($this->weekEvent->getKey())
            ->and(Arr::get($result, 'props.calendarEvents.calendarEvents.0.colour'))
            ->not()->toBeNull()
            ->and(Arr::get($result, 'props.calendarEvents.calendarView'))
            ->toBeArray()
            ->and(Arr::get($result, 'props.calendarEvents.calendarView'))
            ->toHaveCount(7)
            ->and(Arr::get($result, 'props.calendarEvents.calendarView.0.date'))
            ->toBe($weekStartDate->format('Y-m-d'))
            ->and(Arr::get($result, 'props.calendarEvents.calendarView.6.date'))
            ->toBe($weekEndDate->format('Y-m-d'))
            ->and(Arr::get($result, 'props.calendarEvents.calendarView.6.hasEvent'))
            ->tobeTrue()
            ->and(Arr::get($result, 'props.calendarEvents.calendarView.6.isCurrentMonth'))
            ->tobeTrue();
    });
    it('renders Weekly View of Calendar CalendarEvent list page with moving date
        at new day during timezone transformation', function (): void {
        actingAs($this->user);
        auth()->login($this->user);
        $this->user->profile->timezone = 'America/New_York';
        $this->user->profile->save();

        $action = new CalendarsListPage;

        $eventDate = Date::today('America/New_York')
            ->endOfWeek()->setTime(02, 0, 0);
        $this->data['start_date_time'] = $eventDate->copy()
            ->timezone(config('app.timezone'))->format('Y-m-d H:i:s');
        $this->data['end_date_time'] = $eventDate->copy()
            ->addHour()->timezone(config('app.timezone'))->format('Y-m-d H:i:s');
        $this->data['date'] = $eventDate->format('Y-m-d');

        $this->weekEvent = CalendarEvent::factory()->create($this->data);

        $this->weekEvent->calendarEventUsers()->attach($this->user->getKey(),
            [
                'role'   => CalendarEventRoleEnum::HOST,
                'colour' => CalendarEventColoursEnum::BLUE->value,
            ]);

        $requestDate = Date::now('Europe/Kyiv')->format('Y-m-d');
        $requestData = [
            'date'     => $requestDate,
            'mode'     => 'Week view',
        ];

        $request = new Request($requestData);

        $response = $action->handle($request);
        $resultData = $response->toResponse(request())->getOriginalContent();
        $result = $resultData->getData()['page'];

        $timezoneAbbreviation = $eventDate->format('T');
        $expectedDayNumber = (int) $eventDate->format('w') + 1;
        $weekStartDate = Date::parse($requestDate, 'America/New_York')->startOfWeek();
        $weekEndDate = Date::parse($requestDate, 'America/New_York')->endOfWeek();

        expect($response)->toBeInstanceOf(Response::class)
            ->and(Arr::get($result, 'component'))->toBe('Calendar/CalendarEventsList')
            ->and(Arr::get($result, 'props.permissions'))->toBe('create')
            ->and(Arr::get($result, 'props.locale'))->toBe(app()->getLocale())
            ->and(Arr::get($result, 'props.calendarEvents.calendarEvents.0.dayNumber'))
            ->toBe($expectedDayNumber)
            ->and(Arr::get($result, 'props.calendarEvents.calendarEvents.0.webLink'))
            ->toBe($this->data['web_link'])
            ->and(Arr::get($result, 'props.calendarEvents.calendarEvents.0.dateTime'))
            ->toBe($eventDate->format('Y-m-d').'"'
                .$timezoneAbbreviation.'"'.$eventDate->format('H:i:s'))
            ->and(Arr::get($result, 'props.calendarEvents.calendarEvents.0.time'))
            ->toBe('2:00 AM')
            ->and(Arr::get($result, 'props.calendarEvents.calendarEvents.0.startIndex'))
            ->toBe(14)
            ->and(Arr::get($result, 'props.calendarEvents.calendarEvents.0.durationIndex'))
            ->toBe(12)
            ->and(Arr::get($result, 'props.calendarEvents.calendarEvents.0.id'))
            ->toBe($this->weekEvent->getKey())
            ->and(Arr::get($result, 'props.calendarEvents.calendarEvents.0.colour'))
            ->not()->toBeNull()
            ->and(Arr::get($result, 'props.calendarEvents.calendarView'))
            ->toBeArray()
            ->and(Arr::get($result, 'props.calendarEvents.calendarView'))
            ->toHaveCount(7)
            ->and(Arr::get($result, 'props.calendarEvents.calendarView.0.date'))
            ->toBe($weekStartDate->format('Y-m-d'))
            ->and(Arr::get($result, 'props.calendarEvents.calendarView.6.date'))
            ->toBe($weekEndDate->format('Y-m-d'))
            ->and(Arr::get($result, 'props.calendarEvents.calendarView.6.hasEvent'))
            ->tobeTrue();
    });

    it('renders Daily View of Calendar CalendarEvent list page
        with positive timezone difference without changing date during timezone transformation', function (): void {
        actingAs($this->user);
        auth()->login($this->user);
        $action = new CalendarsListPage;
        $this->user->profile->timezone = 'Europe/Kyiv';
        $this->user->profile->save();

        $todayDate = Date::today('Europe/Kyiv')->setTime(15, 0, 0);
        $this->data['start_date_time'] = $todayDate->copy()
            ->timezone(config('app.timezone'))->format('Y-m-d H:i:s');
        $this->data['end_date_time'] = $todayDate->copy()
            ->addHour()->timezone(config('app.timezone'))->format('Y-m-d H:i:s');
        $this->data['date'] = $todayDate->format('Y-m-d');

        $this->dailyEvent = CalendarEvent::factory()->create($this->data);
        $this->dailyEvent->calendarEventUsers()->attach($this->user->getKey(),
            [
                'role'   => CalendarEventRoleEnum::HOST,
                'colour' => CalendarEventColoursEnum::BLUE->value,
            ]);

        $previousMonthDate = Date::today('Europe/Kyiv')
            ->startOfMonth()->subDays(10)->setTime(22, 0, 0);
        $this->data['start_date_time'] = $previousMonthDate->copy()
            ->timezone(config('app.timezone'))->format('Y-m-d H:i:s');
        $this->data['end_date_time'] = $previousMonthDate->copy()
            ->addHour()->timezone(config('app.timezone'))->format('Y-m-d H:i:s');
        $this->data['date'] = $previousMonthDate->format('Y-m-d');

        $this->previousMonthEvent = CalendarEvent::factory()->create($this->data);
        $this->previousMonthEvent->calendarEventUsers()->attach($this->user->getKey(),
            [
                'role'   => CalendarEventRoleEnum::HOST,
                'colour' => CalendarEventColoursEnum::BLUE->value,
            ]);

        $nextMonthDate = Date::today('Europe/Kyiv')
            ->endOfMonth()->addDays(10)->setTime(22, 0, 0);
        $this->data['start_date_time'] = $nextMonthDate->copy()
            ->timezone(config('app.timezone'))->format('Y-m-d H:i:s');
        $this->data['end_date_time'] = $nextMonthDate->copy()
            ->addHour()->timezone(config('app.timezone'))->format('Y-m-d H:i:s');
        $this->data['date'] = $nextMonthDate->format('Y-m-d');

        $this->nextMonthEvent = CalendarEvent::factory()->create($this->data);
        $this->nextMonthEvent->calendarEventUsers()->attach($this->user->getKey(),
            [
                'role'   => CalendarEventRoleEnum::HOST,
                'colour' => CalendarEventColoursEnum::BLUE->value,
            ]);

        $requestDate = Date::now('Europe/Kyiv')->format('Y-m-d');
        $requestData = [
            'date'     => $requestDate,
            'mode'     => 'Day view',
        ];

        $request = new Request($requestData);

        $response = $action->handle($request);
        $resultData = $response->toResponse(request())->getOriginalContent();
        $result = $resultData->getData()['page'];

        $timezoneAbbreviation = $todayDate->format('T');

        expect($response)->toBeInstanceOf(Response::class)
            ->and(Arr::get($result, 'component'))->toBe('Calendar/CalendarEventsList')
            ->and(Arr::get($result, 'props.locale'))->toBe(app()->getLocale())
            ->and(Arr::get($result, 'props.permissions'))->toBe('create')
            ->and(Arr::get($result, 'props.calendarEvents.calendarEvents.0.webLink'))
            ->toBe($this->data['web_link'])
            ->and(Arr::get($result, 'props.calendarEvents.calendarEvents.0.dateTime'))
            ->toBe($todayDate->format('Y-m-d').'"'
                .$timezoneAbbreviation.'"'.$todayDate->format('H:i:s'))
            ->and(Arr::get($result, 'props.calendarEvents.calendarEvents.0.time'))
            ->toBe('3:00 PM')
            ->and(Arr::get($result, 'props.calendarEvents.calendarEvents.0.startIndex'))
            ->toBe(92)
            ->and(Arr::get($result, 'props.calendarEvents.calendarEvents.0.durationIndex'))
            ->toBe(12)
            ->and(Arr::get($result, 'props.calendarEvents.calendarEvents.0.title'))
            ->toBe($this->data['title'])
            ->and(Arr::get($result, 'props.calendarEvents.calendarEvents.0.id'))
            ->toBe($this->dailyEvent->getKey())
            ->and(Arr::get($result, 'props.calendarEvents.calendarEvents.0.colour'))
            ->not()->toBeNull()
            ->and(Arr::get($result, 'props.calendarEvents.calendarView'))
            ->toBeArray()
            ->and(Arr::get($result, 'props.calendarEvents.calendarView'))
            ->toHaveCount(3)
            ->and(Arr::get($result, 'props.calendarEvents.calendarView.'
                    .$previousMonthDate->format('Y-m')))->toBeArray()
            ->and(Arr::get($result, 'props.calendarEvents.calendarView.'
                    .$nextMonthDate->format('Y-m')))->toBeArray()
            ->and(Arr::get($result, 'props.calendarEvents.calendarView.'
                    .Date::today('Europe/Kyiv')->format('Y-m')))->toBeArray()
            ->and(Arr::get($result, 'props.calendarEvents.calendarView.'
                    .Date::today('Europe/Kyiv')->format('Y-m')))->toContainEqual([
                        'date'           => Date::today('Europe/Kyiv')->format('Y-m-d'),
                        'isCurrentMonth' => true,
                        'isSelected'     => true,
                        'isToday'        => true,
                        'hasEvent'       => true,
                    ]);
    });

    it('renders Daily View of Calendar CalendarEvent list page
        with timezone transformation moving date to previous day', function (): void {
        actingAs($this->user);
        auth()->login($this->user);
        $action = new CalendarsListPage;

        $todayDate = Date::today('Europe/Kyiv')->setTime(22, 0, 0);
        $this->data['start_date_time'] = $todayDate->copy()
            ->timezone(config('app.timezone'))->format('Y-m-d H:i:s');
        $this->data['end_date_time'] = $todayDate->copy()
            ->addHour()->timezone(config('app.timezone'))->format('Y-m-d H:i:s');
        $this->data['date'] = $todayDate->format('Y-m-d');

        $this->dailyEvent = CalendarEvent::factory()->create($this->data);
        $this->dailyEvent->calendarEventUsers()->attach($this->user->getKey(),
            [
                'role'   => CalendarEventRoleEnum::HOST,
                'colour' => CalendarEventColoursEnum::BLUE->value,
            ]);

        $previousMonthDate = Date::today('Europe/Kyiv')
            ->startOfMonth()->subDays(10)->setTime(22, 0, 0);
        $this->data['start_date_time'] = $previousMonthDate->copy()
            ->timezone(config('app.timezone'))->format('Y-m-d H:i:s');
        $this->data['end_date_time'] = $previousMonthDate->copy()
            ->addHour()->timezone(config('app.timezone'))->format('Y-m-d H:i:s');
        $this->data['date'] = $previousMonthDate->format('Y-m-d');

        $this->previousMonthEvent = CalendarEvent::factory()->create($this->data);
        $this->previousMonthEvent->calendarEventUsers()->attach($this->user->getKey(),
            [
                'role'   => CalendarEventRoleEnum::HOST,
                'colour' => CalendarEventColoursEnum::BLUE->value,
            ]);

        $nextMonthDate = Date::today('Europe/Kyiv')
            ->endOfMonth()->addDays(10)->setTime(22, 0, 0);
        $this->data['start_date_time'] = $nextMonthDate->copy()
            ->timezone(config('app.timezone'))->format('Y-m-d H:i:s');
        $this->data['end_date_time'] = $nextMonthDate->copy()
            ->addHour()->timezone(config('app.timezone'))->format('Y-m-d H:i:s');
        $this->data['date'] = $nextMonthDate->format('Y-m-d');

        $this->nextMonthEvent = CalendarEvent::factory()->create($this->data);
        $this->nextMonthEvent->calendarEventUsers()->attach($this->user->getKey(),
            [
                'role'   => CalendarEventRoleEnum::HOST,
                'colour' => CalendarEventColoursEnum::BLUE->value,
            ]);

        $requestDate = Date::now('Europe/Kyiv')->format('Y-m-d');
        $requestData = [
            'date'     => $requestDate,
            'mode'     => 'Day view',
        ];

        $request = new Request($requestData);

        $response = $action->handle($request);
        $resultData = $response->toResponse(request())->getOriginalContent();
        $result = $resultData->getData()['page'];

        $timezoneAbbreviation = $todayDate->format('T');

        expect($response)->toBeInstanceOf(Response::class)
            ->and(Arr::get($result, 'component'))->toBe('Calendar/CalendarEventsList')
            ->and(Arr::get($result, 'props.locale'))->toBe(app()->getLocale())
            ->and(Arr::get($result, 'props.permissions'))->toBe('create')
            ->and(Arr::get($result, 'props.calendarEvents.calendarEvents.0.webLink'))
            ->toBe($this->data['web_link'])
            ->and(Arr::get($result, 'props.calendarEvents.calendarEvents.0.dateTime'))
            ->toBe($todayDate->format('Y-m-d').'"'
                .$timezoneAbbreviation.'"'.$todayDate->format('H:i:s'))
            ->and(Arr::get($result, 'props.calendarEvents.calendarEvents.0.time'))
            ->toBe('10:00 PM')
            ->and(Arr::get($result, 'props.calendarEvents.calendarEvents.0.startIndex'))
            ->toBe(134)
            ->and(Arr::get($result, 'props.calendarEvents.calendarEvents.0.durationIndex'))
            ->toBe(12)
            ->and(Arr::get($result, 'props.calendarEvents.calendarEvents.0.title'))
            ->toBe($this->data['title'])
            ->and(Arr::get($result, 'props.calendarEvents.calendarEvents.0.id'))
            ->toBe($this->dailyEvent->getKey())
            ->and(Arr::get($result, 'props.calendarEvents.calendarEvents.0.colour'))
            ->not()->toBeNull()
            ->and(Arr::get($result, 'props.calendarEvents.calendarView'))
            ->toBeArray()
            ->and(Arr::get($result, 'props.calendarEvents.calendarView'))
            ->toHaveCount(3)
            ->and(Arr::get($result, 'props.calendarEvents.calendarView.'
                    .$previousMonthDate->format('Y-m')))->toBeArray()
            ->and(Arr::get($result, 'props.calendarEvents.calendarView.'
                    .$nextMonthDate->format('Y-m')))->toBeArray()
            ->and(Arr::get($result, 'props.calendarEvents.calendarView.'
                    .Date::today('Europe/Kyiv')->format('Y-m')))->toBeArray()
            ->and(Arr::get($result, 'props.calendarEvents.calendarView.'
                    .Date::today('Europe/Kyiv')->format('Y-m')))->toContainEqual([
                        'date'           => Date::today('Europe/Kyiv')->format('Y-m-d'),
                        'isCurrentMonth' => true,
                        'isSelected'     => true,
                        'isToday'        => true,
                        'hasEvent'       => true,
                    ]);
    });

    it('renders Daily View of Calendar CalendarEvent list page
        with timezone transformation moving date to next day', function (): void {
        actingAs($this->user);
        auth()->login($this->user);
        $this->user->profile->timezone = 'America/New_York';
        $this->user->profile->save();

        $action = new CalendarsListPage;

        $todayDate = Date::today('America/New_York')->setTime(02, 0, 0);
        $this->data['start_date_time'] = $todayDate->copy()
            ->timezone(config('app.timezone'))->format('Y-m-d H:i:s');
        $this->data['end_date_time'] = $todayDate->copy()
            ->addHour()->timezone(config('app.timezone'))->format('Y-m-d H:i:s');
        $this->data['date'] = $todayDate->format('Y-m-d');

        $this->dailyEvent = CalendarEvent::factory()->create($this->data);
        $this->dailyEvent->calendarEventUsers()->attach($this->user->getKey(), [
            'role'   => CalendarEventRoleEnum::HOST,
            'colour' => CalendarEventColoursEnum::BLUE->value,
        ]);

        $previousMonthDate = Date::today('America/New_York')
            ->startOfMonth()->subDays(10)->setTime(02, 0, 0);
        $this->data['start_date_time'] = $previousMonthDate->copy()
            ->timezone(config('app.timezone'))->format('Y-m-d H:i:s');
        $this->data['end_date_time'] = $previousMonthDate->copy()
            ->addHour()->timezone(config('app.timezone'))->format('Y-m-d H:i:s');
        $this->data['date'] = $previousMonthDate->format('Y-m-d');

        $this->previousMonthEvent = CalendarEvent::factory()->create($this->data);
        $this->previousMonthEvent->calendarEventUsers()->attach($this->user->getKey(),
            [
                'role'   => CalendarEventRoleEnum::HOST,
                'colour' => CalendarEventColoursEnum::BLUE->value,
            ]);

        $nextMonthDate = Date::today('America/New_York')->endOfMonth()
            ->addDays(10)->setTime(02, 0, 0);
        $this->data['start_date_time'] = $nextMonthDate->copy()
            ->timezone(config('app.timezone'))->format('Y-m-d H:i:s');
        $this->data['end_date_time'] = $nextMonthDate->copy()
            ->addHour()->timezone(config('app.timezone'))->format('Y-m-d H:i:s');
        $this->data['date'] = $nextMonthDate->format('Y-m-d');

        $this->nextMonthEvent = CalendarEvent::factory()->create($this->data);
        $this->nextMonthEvent->calendarEventUsers()->attach($this->user->getKey(),
            [
                'role'   => CalendarEventRoleEnum::HOST,
                'colour' => CalendarEventColoursEnum::BLUE->value,
            ]);

        $requestDate = Date::now('America/New_York')->format('Y-m-d');
        $requestData = [
            'date'     => $requestDate,
            'mode'     => 'Day view',
        ];

        $request = new Request($requestData);

        $response = $action->handle($request);
        $resultData = $response->toResponse(request())->getOriginalContent();
        $result = $resultData->getData()['page'];

        $timezoneAbbreviation = $todayDate->format('T');

        expect($response)->toBeInstanceOf(Response::class)
            ->and(Arr::get($result, 'component'))->toBe('Calendar/CalendarEventsList')
            ->and(Arr::get($result, 'props.locale'))->toBe(app()->getLocale())
            ->and(Arr::get($result, 'props.permissions'))->toBe('create')
            ->and(Arr::get($result, 'props.calendarEvents.calendarEvents.0.webLink'))
            ->toBe($this->data['web_link'])
            ->and(Arr::get($result, 'props.calendarEvents.calendarEvents.0.dateTime'))
            ->toBe($todayDate->format('Y-m-d').'"'
                .$timezoneAbbreviation.'"'.$todayDate->format('H:i:s'))
            ->and(Arr::get($result, 'props.calendarEvents.calendarEvents.0.time'))
            ->toBe('2:00 AM')
            ->and(Arr::get($result, 'props.calendarEvents.calendarEvents.0.startIndex'))
            ->toBe(14)
            ->and(Arr::get($result, 'props.calendarEvents.calendarEvents.0.durationIndex'))
            ->toBe(12)
            ->and(Arr::get($result, 'props.calendarEvents.calendarEvents.0.title'))
            ->toBe($this->data['title'])
            ->and(Arr::get($result, 'props.calendarEvents.calendarEvents.0.id'))
            ->toBe($this->dailyEvent->getKey())
            ->and(Arr::get($result, 'props.calendarEvents.calendarEvents.0.colour'))
            ->not()->toBeNull()
            ->and(Arr::get($result, 'props.calendarEvents.calendarView'))
            ->toBeArray()
            ->and(Arr::get($result, 'props.calendarEvents.calendarView'))
            ->toHaveCount(3)
            ->and(Arr::get($result, 'props.calendarEvents.calendarView.'
                    .$previousMonthDate->format('Y-m')))->toBeArray()
            ->and(Arr::get($result, 'props.calendarEvents.calendarView.'
                    .$nextMonthDate->format('Y-m')))->toBeArray()
            ->and(Arr::get($result, 'props.calendarEvents.calendarView.'
                    .Date::today('America/New_York')->format('Y-m')))->toBeArray()
            ->and(Arr::get($result, 'props.calendarEvents.calendarView.'
                    .Date::today('America/New_York')->format('Y-m')))->toContainEqual([
                        'date'   => Date::today('America/New_York')
                            ->format('Y-m-d'),
                        'isCurrentMonth' => true,
                        'isSelected'     => true,
                        'isToday'        => true,
                        'hasEvent'       => true,
                    ]);
    });

    it('returns week/daily/month events for mentor user depending on mode', function (): void {
        $user = User::factory()->create();
        $user->assignRole(Role::findByName(RoleEnum::MENTOR->value));
        Auth::login($user);

        $date = Date::now(config('app.timezone'))->format('Y-m-d');

        $action = new CalendarsListPage;

        $reqMonth = Request::create('/calendar', 'GET',
            [
                'mode'     => 'Month view',
                'date'     => $date,
                'timezone' => config('app.timezone'),
            ]);
        $resMonth = inertiaProps($action->handle($reqMonth));
        expect($resMonth['permissions'])->toBe('create');

        $reqWeek = Request::create('/calendar', 'GET',
            [
                'mode' => 'Week view',
                'date' => $date, 'timezone' => config('app.timezone'),
            ]);
        $resWeek = inertiaProps($action->handle($reqWeek));
        expect($resWeek['permissions'])->toBe('create');

        $reqDay = Request::create('/calendar', 'GET',
            [
                'mode'     => 'Day view',
                'date'     => $date,
                'timezone' => config('app.timezone'),
            ]);
        $resDay = inertiaProps($action->handle($reqDay));
        expect($resDay['permissions'])->toBe('create');
    });

    it('uses current date when date param is missing', function (): void {
        $user = User::factory()->create();
        $user->assignRole(Role::findByName(RoleEnum::MENTOR->value));
        Auth::login($user);

        $action = new CalendarsListPage;
        $request = Request::create('/calendar', 'GET',
            [
                'timezone' => 'Europe/Kyiv',
                'mode'     => 'Month view',
            ]);
        $response = $action->handle($request);

        expect($response)->toBeInstanceOf(Response::class);
        // Should not throw error and should use Date::now()
    });

    it('returns empty events array when timezone is missing', function (): void {
        $user = User::factory()->create();
        $user->assignRole(Role::findByName(RoleEnum::MENTOR->value));
        Auth::login($user);

        $action = new CalendarsListPage;
        $request = Request::create('/calendar', 'GET',
            [
                'mode' => 'Day view',
                'date' => Date::now()->format('Y-m-d'),
            ]);
        $response = $action->handle($request);

        $props = inertiaProps($response);
        expect($props['calendarEvents']['calendarEvents'])->toBe([]);
    });

    it('defaults to Month view when mode is not provided', function (): void {
        $user = User::factory()->create();
        $user->assignRole(Role::findByName(RoleEnum::MENTOR->value));
        Auth::login($user);

        $action = new CalendarsListPage;
        // No mode parameter
        $request = Request::create('/calendar', 'GET',
            [
                'timezone' => config('app.timezone'),
                'date'     => Date::now()->format('Y-m-d'),
            ]);
        $response = $action->handle($request);

        $props = inertiaProps($response);
        expect($props['calendarEvents'])->toBeArray();
        // Should default to Month view and call GetMonthCalendarEventsService
    });

    it('includes availableColours in response', function (): void {
        $user = User::factory()->create();
        Auth::login($user);

        $action = new CalendarsListPage;
        $request = Request::create('/calendar', 'GET',
            [
                'timezone' => config('app.timezone'),
            ]);
        $response = $action->handle($request);

        $props = inertiaProps($response);
        expect($props)->toHaveKey('availableColours')
            ->and($props['availableColours'])->toBeArray()
            ->and($props['availableColours'])->not->toBeEmpty();
    });

    it('returns edit permission only when user has mentor role', function (): void {
        $user = User::factory()->create();
        $user->assignRole(Role::findByName(RoleEnum::MENTOR->value));
        Auth::login($user);

        $action = new CalendarsListPage;
        $request = Request::create('/calendar', 'GET',
            [
                'timezone' => config('app.timezone'),
            ]);
        $response = $action->handle($request);

        $props = inertiaProps($response);
        expect($props['permissions'])->toBe('create');
    });
});
