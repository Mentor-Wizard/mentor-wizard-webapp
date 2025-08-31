<?php

declare(strict_types=1);

use App\Actions\Pages\Calendar\CalendarsListPage;
use App\Enums\EventRoleEnum;
use App\Enums\EventStatusEnum;
use App\Enums\EventTypeEnum;
use App\Enums\RoleEnum;
use App\Models\Event as EventModel;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Response;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;

mutates(CalendarsListPage::class);

describe('List Calendar Event Page', function (): void {
    beforeEach(function (): void {

        $this->seed(RoleSeeder::class);
        $this->user = User::factory()->create();
        $this->user->assignRole(Role::findByName(RoleEnum::MENTOR->value));

        $this->nonMentorUser = User::factory()->create();
        actingAs($this->user);
        auth()->login($this->user);
        $this->data = [
            'title'             => 'Default event',
            'status'            => EventStatusEnum::CONFIRMED,
            'start_date_time'   => Carbon::today()->endOfMonth()->endOfWeek()->format('Y-m-d').' 23:59:00',
            'date'              => Carbon::today()->endOfMonth()->endOfWeek()->format('Y-m-d'),
            'duration'          => 3600,
            'type'              => EventTypeEnum::INDIVIDUAL->value,
            'web_link'          => 'https://www.google.com',
            'description'       => 'Test description',
            'mentor_program_id' => null,
        ];
        $this->monthEvent = EventModel::factory()->create($this->data);
        $this->monthEvent->users()->attach($this->user->getKey(), ['role' => EventRoleEnum::HOST]);
    });

    it('renders Month View of Calendar Event list page, without events before', function (): void {
        actingAs($this->user);
        auth()->login($this->user);
        $action = new CalendarsListPage;

        $requestData = [
            'timezone' => 'Europe/Kyiv',
            'date'     => Carbon::now('Europe/Kyiv')->format('Y-m-d'),
            'mode'     => 'Month view',
        ];

        $request = new Request($requestData);

        $response = $action->handle($request);
        $resultData = $response->toResponse(request())->getOriginalContent();
        $result = $resultData->getData()['page'];

        $firstDate = Carbon::today()->startOfMonth()->startOfWeek()->format('Y-m-d');
        $lastdate = Carbon::today()->endOfMonth()->endOfWeek()->format('Y-m-d');
        $difference = Carbon::parse($firstDate)->diffInDays(Carbon::parse($lastdate));

        expect($response)->toBeInstanceOf(Response::class)
            ->and(Arr::get($result, 'component'))->toBe('Calendar/CalendarsList')
            ->and(Arr::get($result, 'props.canLogin'))->toBeTrue()
            ->and(Arr::get($result, 'props.canRegister'))->toBeTrue()
            ->and(Arr::get($result, 'props.locale'))->toBe(app()->getLocale())
            ->and(Arr::get($result, 'props.permissions'))->toBe('edit')
            ->and(Arr::get($result, 'props.events.hasEventsBefore'))->toBeFalse()
            ->and(Arr::get($result, 'props.events.hasEventsAfter'))->toBeFalse()
            ->and(Arr::get($result, 'props.events.calendarView.'.$difference.'.events.0.name'))->toBe($this->data['title'])
            ->and(Arr::get($result, 'props.events.calendarView.'.$difference.'.events.0.href'))->toBe($this->data['web_link'])
            ->and(Arr::get($result, 'props.events.calendarView.'.$difference.'.events.0.datetime'))
            ->toBe(Carbon::today()->endOfMonth()->endOfWeek()->format('Y-m-d').'T23:59')
            ->and(Arr::get($result, 'props.events.calendarView.'.$difference.'.events.0.time'))->toBe('11PM')
            ->and(Arr::get($result, 'props.events.calendarView.'.$difference.'.events.0.id'))->toBe($this->monthEvent->getKey())
            ->and(Arr::get($result, 'props.events.calendarView.'.$difference.'.date'))->toBe(Carbon::today()->endOfMonth()->endOfWeek()->format('Y-m-d'));
    });

    it('renders Month View of Calendar Event list page, with events after and before', function (): void {
        actingAs($this->user);
        auth()->login($this->user);
        $action = new CalendarsListPage;

        $previousMonthDate = Carbon::today('Europe/Kyiv')->startOfMonth()->subDays(10);
        $this->data['start_date_time'] = $previousMonthDate->format('Y-m-d').' 22:00:00';
        $this->data['date'] = $previousMonthDate->format('Y-m-d');

        $this->previousMonthEventMonthChecking = EventModel::factory()->create($this->data);
        $this->previousMonthEventMonthChecking->users()->attach($this->user->getKey(), ['role' => EventRoleEnum::HOST]);

        $nextMonthDate = Carbon::today()->endOfMonth()->addDays(10);
        $this->data['start_date_time'] = $nextMonthDate->format('Y-m-d').' 22:00:00';
        $this->data['date'] = $nextMonthDate->format('Y-m-d');

        $this->nextMonthEventMonthChecking = EventModel::factory()->create($this->data);
        $this->nextMonthEventMonthChecking->users()->attach($this->user->getKey(), ['role' => EventRoleEnum::HOST]);

        $requestData = [
            'timezone' => 'Europe/Kyiv',
            'date'     => Carbon::now('Europe/Kyiv')->format('Y-m-d'),
            'mode'     => 'Month view',
        ];

        $request = new Request($requestData);

        $response = $action->handle($request);
        $resultData = $response->toResponse(request())->getOriginalContent();
        $result = $resultData->getData()['page'];

        $firstDate = Carbon::today()->startOfMonth()->startOfWeek()->format('Y-m-d');
        $lastdate = Carbon::today()->endOfMonth()->endOfWeek()->format('Y-m-d');
        $difference = Carbon::parse($firstDate)->diffInDays(Carbon::parse($lastdate));

        expect($response)->toBeInstanceOf(Response::class)
            ->and(Arr::get($result, 'component'))->toBe('Calendar/CalendarsList')
            ->and(Arr::get($result, 'props.permissions'))->toBe('edit')
            ->and(Arr::get($result, 'props.canLogin'))->toBeTrue()
            ->and(Arr::get($result, 'props.canRegister'))->toBeTrue()
            ->and(Arr::get($result, 'props.locale'))->toBe(app()->getLocale())
            ->and(Arr::get($result, 'props.permissions'))->toBe('edit')
            ->and(Arr::get($result, 'props.events.hasEventsBefore'))->toBeTrue()
            ->and(Arr::get($result, 'props.events.hasEventsAfter'))->toBeTrue()
            ->and(Arr::get($result, 'props.events.calendarView.'.$difference.'.events.0.name'))->toBe($this->data['title'])
            ->and(Arr::get($result, 'props.events.calendarView.'.$difference.'.events.0.href'))->toBe($this->data['web_link'])
            ->and(Arr::get($result, 'props.events.calendarView.'.$difference.'.events.0.datetime'))->toBe(Carbon::today()->endOfMonth()->endOfWeek()->format('Y-m-d').'T23:59')
            ->and(Arr::get($result, 'props.events.calendarView.'.$difference.'.events.0.time'))->toBe('11PM')
            ->and(Arr::get($result, 'props.events.calendarView.'.$difference.'.events.0.id'))->toBe($this->monthEvent->getKey())
            ->and(Arr::get($result, 'props.events.calendarView.'.$difference.'.date'))->toBe(Carbon::today()->endOfMonth()->endOfWeek()->format('Y-m-d'));
    });

    it('renders Weekly View of Calendar Event list page', function (): void {
        actingAs($this->user);
        auth()->login($this->user);
        $action = new CalendarsListPage;

        $this->data['start_date_time'] = Carbon::today('Europe/Kyiv')->endOfWeek()->format('Y-m-d').' 22:00:00';
        $this->data['date'] = Carbon::today('Europe/Kyiv')->endOfWeek()->format('Y-m-d');

        $this->weekEvent = EventModel::factory()->create($this->data);
        $this->weekEvent->users()->attach($this->user->getKey(), ['role' => EventRoleEnum::HOST]);

        $requestData = [
            'timezone' => 'Europe/Kyiv',
            'date'     => Carbon::now('Europe/Kyiv')->format('Y-m-d'),
            'mode'     => 'Week view',
        ];

        $request = new Request($requestData);

        $response = $action->handle($request);
        $resultData = $response->toResponse(request())->getOriginalContent();
        $result = $resultData->getData()['page'];

        expect($response)->toBeInstanceOf(Response::class)
            ->and(Arr::get($result, 'component'))->toBe('Calendar/CalendarsList')
            ->and(Arr::get($result, 'props.permissions'))->toBe('edit')
            ->and(Arr::get($result, 'props.canLogin'))->toBeTrue()
            ->and(Arr::get($result, 'props.canRegister'))->toBeTrue()
            ->and(Arr::get($result, 'props.locale'))->toBe(app()->getLocale())
            ->and(Arr::get($result, 'props.permissions'))->toBe('edit')
            ->and(Arr::get($result, 'props.events.events.0.dayNumber'))->toBe(1)
            ->and(Arr::get($result, 'props.events.events.0.href'))->toBe($this->data['web_link'])
            ->and(Arr::get($result, 'props.events.events.0.dateTime'))->toBe(Carbon::today('Europe/Kyiv')->endOfWeek()->format('Y-m-d').'"UTC"22:00:00')
            ->and(Arr::get($result, 'props.events.events.0.time'))->toBe('10:00 PM')
            ->and(Arr::get($result, 'props.events.events.0.startIndex'))->toBe(134)
            ->and(Arr::get($result, 'props.events.events.0.durationIndex'))->toBe(12)
            ->and(Arr::get($result, 'props.events.events.0.id'))->toBe($this->weekEvent->getKey())
            ->and(Arr::get($result, 'props.events.events.0.colour'))->not()->toBeNull()
            ->and(Arr::get($result, 'props.events.calendarView'))->toBeArray()
            ->and(Arr::get($result, 'props.events.calendarView'))->toHaveCount(7)
            ->and(Arr::get($result, 'props.events.calendarView.0.date'))->toBe(Carbon::today()->startOfWeek()->format('Y-m-d'))
            ->and(Arr::get($result, 'props.events.calendarView.6.date'))->toBe(Carbon::today()->endOfWeek()->format('Y-m-d'))
            ->and(Arr::get($result, 'props.events.calendarView.6.hasEvent'))->tobeTrue()
            ->and(Arr::get($result, 'props.events.calendarView.6.isCurrentMonth'))->tobeTrue();
    });

    it('renders Daily View of Calendar Event list page', function (): void {
        actingAs($this->user);
        auth()->login($this->user);
        $action = new CalendarsListPage;

        $todayDate = Carbon::today('Europe/Kyiv');
        $this->data['start_date_time'] = $todayDate->format('Y-m-d').' 22:00:00';
        $this->data['date'] = $todayDate->format('Y-m-d');

        $this->dailyEvent = EventModel::factory()->create($this->data);
        $this->dailyEvent->users()->attach($this->user->getKey(), ['role' => EventRoleEnum::HOST]);

        $previousMonthDate = Carbon::today('Europe/Kyiv')->startOfMonth()->subDays(10);
        $this->data['start_date_time'] = $previousMonthDate->format('Y-m-d').' 22:00:00';
        $this->data['date'] = $previousMonthDate->format('Y-m-d');

        $this->previousMonthEvent = EventModel::factory()->create($this->data);
        $this->previousMonthEvent->users()->attach($this->user->getKey(), ['role' => EventRoleEnum::HOST]);

        $nextMonthDate = Carbon::today('Europe/Kyiv')->endOfMonth()->addDays(10);
        $this->data['start_date_time'] = $nextMonthDate->format('Y-m-d').' 22:00:00';
        $this->data['date'] = $nextMonthDate->format('Y-m-d');

        $this->nextMonthEvent = EventModel::factory()->create($this->data);
        $this->nextMonthEvent->users()->attach($this->user->getKey(), ['role' => EventRoleEnum::HOST]);

        $requestData = [
            'timezone' => 'Europe/Kyiv',
            'date'     => Carbon::now('Europe/Kyiv')->format('Y-m-d'),
            'mode'     => 'Day view',
        ];

        $request = new Request($requestData);

        $response = $action->handle($request);
        $resultData = $response->toResponse(request())->getOriginalContent();
        $result = $resultData->getData()['page'];

        expect($response)->toBeInstanceOf(Response::class)
            ->and(Arr::get($result, 'component'))->toBe('Calendar/CalendarsList')
            ->and(Arr::get($result, 'props.canLogin'))->toBeTrue()
            ->and(Arr::get($result, 'props.canRegister'))->toBeTrue()
            ->and(Arr::get($result, 'props.locale'))->toBe(app()->getLocale())
            ->and(Arr::get($result, 'props.permissions'))->toBe('edit')
            ->and(Arr::get($result, 'props.events.events.0.href'))->toBe($this->data['web_link'])
            ->and(Arr::get($result, 'props.events.events.0.dateTime'))->toBe($todayDate->format('Y-m-d').'"UTC"22:00:00')
            ->and(Arr::get($result, 'props.events.events.0.time'))->toBe('10:00 PM')
            ->and(Arr::get($result, 'props.events.events.0.startIndex'))->toBe(134)
            ->and(Arr::get($result, 'props.events.events.0.durationIndex'))->toBe(12)
            ->and(Arr::get($result, 'props.events.events.0.title'))->toBe($this->data['title'])
            ->and(Arr::get($result, 'props.events.events.0.id'))->toBe($this->dailyEvent->getKey())
            ->and(Arr::get($result, 'props.events.events.0.colour'))->not()->toBeNull()
            ->and(Arr::get($result, 'props.events.calendarView'))->toBeArray()
            ->and(Arr::get($result, 'props.events.calendarView'))->toHaveCount(3)
            ->and(Arr::get($result, 'props.events.calendarView.'.$previousMonthDate->format('Y-m')))->toBeArray()
            ->and(Arr::get($result, 'props.events.calendarView.'.$nextMonthDate->format('Y-m')))->toBeArray()
            ->and(Arr::get($result, 'props.events.calendarView.'.$todayDate->format('Y-m')))->toBeArray()
            ->and(Arr::get($result, 'props.events.calendarView.'.$todayDate->format('Y-m')))->toContainEqual([
                'date'           => $todayDate->format('Y-m-d'),
                'isCurrentMonth' => true,
                'isSelected'     => true,
                'isToday'        => true,
                'hasEvent'       => true,
            ]);
    });
});
