<?php

declare(strict_types=1);

use App\Actions\Pages\Calendar\CalendarsListPage;
use App\Enums\CalendarEventColoursEnum;
use App\Enums\CalendarEventRoleEnum;
use App\Enums\CalendarEventStatusEnum;
use App\Enums\CalendarEventTypeEnum;
use App\Enums\RoleEnum;
use App\Models\CalendarEvent;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Inertia\Response;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;

mutates(CalendarsListPage::class);

describe('List Calendar CalendarEvent Page', function (): void {
    beforeEach(function (): void {

        $this->seed(RoleSeeder::class);
        $this->user = User::factory()->create();
        $this->user->assignRole(Role::findByName(RoleEnum::MENTOR->value));

        $this->nonMentorUser = User::factory()->create();
        actingAs($this->user);
        auth()->login($this->user);
        $this->data = [
            'title'             => 'Default event',
            'status'            => CalendarEventStatusEnum::CONFIRMED,
            'start_date_time'   => Illuminate\Support\Facades\Date::today()->endOfMonth()->endOfWeek()->setTime(19, 59, 0)->setTimezone('Europe/Kyiv')->format('Y-m-d H:i:s'),
            'date'              => Illuminate\Support\Facades\Date::today()->endOfMonth()->endOfWeek()->setTime(19, 59, 0)->setTimezone('Europe/Kyiv')->format('Y-m-d'),
            'duration'          => 3600,
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'web_link'          => 'https://www.google.com',
            'description'       => 'Test description',
            'mentor_program_id' => null,
        ];
        $this->monthEvent = CalendarEvent::factory()->create($this->data);
        $this->monthEvent->calendarEventUsers()->attach($this->user->getKey(), ['role' => CalendarEventRoleEnum::HOST, 'colour' => CalendarEventColoursEnum::BLUE->value]);
    });

    it('renders Month View of Calendar CalendarEvent list page, without events before', function (): void {
        actingAs($this->user);
        auth()->login($this->user);
        $action = new CalendarsListPage;

        $requestData = [
            'timezone' => 'Europe/Kyiv',
            'date'     => Illuminate\Support\Facades\Date::now('Europe/Kyiv')->format('Y-m-d'),
            'mode'     => 'Month view',
        ];

        $request = new Request($requestData);

        $response = $action->handle($request);
        $resultData = $response->toResponse(request())->getOriginalContent();
        $result = $resultData->getData()['page'];

        $firstDate = Illuminate\Support\Facades\Date::today()->startOfMonth()->startOfWeek()->format('Y-m-d');
        $lastdate = Illuminate\Support\Facades\Date::today()->endOfMonth()->endOfWeek()->format('Y-m-d');
        $difference = Illuminate\Support\Facades\Date::parse($firstDate)->diffInDays(Illuminate\Support\Facades\Date::parse($lastdate));
        expect($response)->toBeInstanceOf(Response::class)
            ->and(Arr::get($result, 'component'))->toBe('Calendar/CalendarsList')
            ->and(Arr::get($result, 'props.canLogin'))->toBeTrue()
            ->and(Arr::get($result, 'props.canRegister'))->toBeTrue()
            ->and(Arr::get($result, 'props.locale'))->toBe(app()->getLocale())
            ->and(Arr::get($result, 'props.laravelVersion'))->toBe(Application::VERSION)
            ->and(Arr::get($result, 'props.phpVersion'))->toBe(PHP_VERSION)
            ->and(Arr::get($result, 'props.permissions'))->toBe('edit')
            ->and(Arr::get($result, 'props.events.hasEventsBefore'))->toBeFalse()
            ->and(Arr::get($result, 'props.events.hasEventsAfter'))->toBeFalse()
            ->and(Arr::get($result, 'props.events.calendarView.'.$difference.'.events.0.name'))->toBe($this->data['title'])
            ->and(Arr::get($result, 'props.events.calendarView.'.$difference.'.events.0.href'))->toBe($this->data['web_link'])
            ->and(Arr::get($result, 'props.events.calendarView.'.$difference.'.events.0.datetime'))
            ->toBe(Illuminate\Support\Facades\Date::today()->endOfMonth()->endOfWeek()->setTime(19, 59, 0)
                ->setTimezone('Europe/Kyiv')->format('Y-m-d\TH:i'))
            ->and(Arr::get($result, 'props.events.calendarView.'.$difference.'.events.0.time'))->toBe('9PM')
            ->and(Arr::get($result, 'props.events.calendarView.'.$difference.'.events.0.id'))->toBe($this->monthEvent->getKey())
            ->and(Arr::get($result, 'props.events.calendarView.'.$difference.'.date'))->toBe(Illuminate\Support\Facades\Date::today()->endOfMonth()
            ->endOfWeek()->setTime(19, 59, 0)->setTimezone('Europe/Kyiv')->format('Y-m-d'));
    });

    it('renders Month View of Calendar CalendarEvent list page, with events after and before', function (): void {
        actingAs($this->user);
        auth()->login($this->user);
        $action = new CalendarsListPage;

        $previousMonthDate = Illuminate\Support\Facades\Date::today('Europe/Kyiv')->startOfMonth()->subDays(10);
        $this->data['start_date_time'] = $previousMonthDate->format('Y-m-d').' 22:00:00';
        $this->data['date'] = $previousMonthDate->format('Y-m-d');

        $this->previousMonthEventMonthChecking = CalendarEvent::factory()->create($this->data);
        $this->previousMonthEventMonthChecking->calendarEventUsers()->attach($this->user->getKey(), ['role' => CalendarEventRoleEnum::HOST, 'colour' => CalendarEventColoursEnum::BLUE->value]);

        $nextMonthDate = Illuminate\Support\Facades\Date::today()->endOfMonth()->addDays(10);
        $this->data['start_date_time'] = $nextMonthDate->format('Y-m-d').' 22:00:00';
        $this->data['date'] = $nextMonthDate->format('Y-m-d');

        $this->nextMonthEventMonthChecking = CalendarEvent::factory()->create($this->data);
        $this->nextMonthEventMonthChecking->calendarEventUsers()->attach($this->user->getKey(), ['role' => CalendarEventRoleEnum::HOST, 'colour' => CalendarEventColoursEnum::BLUE->value]);

        $requestData = [
            'timezone' => 'Europe/Kyiv',
            'date'     => Illuminate\Support\Facades\Date::now('Europe/Kyiv')->format('Y-m-d'),
            'mode'     => 'Month view',
        ];

        $request = new Request($requestData);

        $response = $action->handle($request);
        $resultData = $response->toResponse(request())->getOriginalContent();
        $result = $resultData->getData()['page'];

        $firstDate = Illuminate\Support\Facades\Date::today()->startOfMonth()->startOfWeek()->format('Y-m-d');
        $lastdate = Illuminate\Support\Facades\Date::today()->endOfMonth()->endOfWeek()->format('Y-m-d');
        $difference = Illuminate\Support\Facades\Date::parse($firstDate)->diffInDays(Illuminate\Support\Facades\Date::parse($lastdate));

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
            ->and(Arr::get($result, 'props.events.calendarView.'.$difference.'.events.0.datetime'))->toBe(Illuminate\Support\Facades\Date::today()->endOfMonth()->endOfWeek()
            ->setTime(19, 59, 0)->setTimezone('Europe/Kyiv')->format('Y-m-d\TH:i'))
            ->and(Arr::get($result, 'props.events.calendarView.'.$difference.'.events.0.time'))->toBe('9PM')
            ->and(Arr::get($result, 'props.events.calendarView.'.$difference.'.events.0.id'))->toBe($this->monthEvent->getKey())
            ->and(Arr::get($result, 'props.events.calendarView.'.$difference.'.date'))
            ->toBe(Illuminate\Support\Facades\Date::today()->endOfMonth()->endOfWeek()->setTime(19, 59, 0)
                ->setTimezone('Europe/Kyiv')->format('Y-m-d'));
    });

    it('renders Weekly View of Calendar CalendarEvent list page', function (): void {
        actingAs($this->user);
        auth()->login($this->user);
        $action = new CalendarsListPage;

        $eventDate = Illuminate\Support\Facades\Date::today('Europe/Kyiv')->endOfWeek()->setTime(22, 0, 0);
        $this->data['start_date_time'] = $eventDate->copy()->setTimezone('UTC')->format('Y-m-d H:i:s');
        $this->data['date'] = $eventDate->format('Y-m-d');

        $this->weekEvent = CalendarEvent::factory()->create($this->data);
        $this->weekEvent->calendarEventUsers()->attach($this->user->getKey(), ['role' => CalendarEventRoleEnum::HOST,
            'colour'                                                                  => CalendarEventColoursEnum::BLUE->value]);

        $requestDate = Illuminate\Support\Facades\Date::now('Europe/Kyiv')->format('Y-m-d');
        $requestData = [
            'timezone' => 'Europe/Kyiv',
            'date'     => $requestDate,
            'mode'     => 'Week view',
        ];

        $request = new Request($requestData);

        $response = $action->handle($request);
        $resultData = $response->toResponse(request())->getOriginalContent();
        $result = $resultData->getData()['page'];

        $timezoneAbbreviation = $eventDate->format('T');
        $expectedDayNumber = (int) $eventDate->format('w') + 1;
        $weekStartDate = Illuminate\Support\Facades\Date::parse($requestDate, 'Europe/Kyiv')->startOfWeek();
        $weekEndDate = Illuminate\Support\Facades\Date::parse($requestDate, 'Europe/Kyiv')->endOfWeek();

        expect($response)->toBeInstanceOf(Response::class)
            ->and(Arr::get($result, 'component'))->toBe('Calendar/CalendarsList')
            ->and(Arr::get($result, 'props.permissions'))->toBe('edit')
            ->and(Arr::get($result, 'props.canLogin'))->toBeTrue()
            ->and(Arr::get($result, 'props.canRegister'))->toBeTrue()
            ->and(Arr::get($result, 'props.locale'))->toBe(app()->getLocale())
            ->and(Arr::get($result, 'props.permissions'))->toBe('edit')
            ->and(Arr::get($result, 'props.events.events.0.dayNumber'))->toBe($expectedDayNumber)
            ->and(Arr::get($result, 'props.events.events.0.href'))->toBe($this->data['web_link'])
            ->and(Arr::get($result, 'props.events.events.0.dateTime'))->toBe($eventDate->format('Y-m-d').'"'.$timezoneAbbreviation.'"'.$eventDate->format('H:i:s'))
            ->and(Arr::get($result, 'props.events.events.0.time'))->toBe('10:00 PM')
            ->and(Arr::get($result, 'props.events.events.0.startIndex'))->toBe(134)
            ->and(Arr::get($result, 'props.events.events.0.durationIndex'))->toBe(12)
            ->and(Arr::get($result, 'props.events.events.0.id'))->toBe($this->weekEvent->getKey())
            ->and(Arr::get($result, 'props.events.events.0.colour'))->not()->toBeNull()
            ->and(Arr::get($result, 'props.events.calendarView'))->toBeArray()
            ->and(Arr::get($result, 'props.events.calendarView'))->toHaveCount(7)
            ->and(Arr::get($result, 'props.events.calendarView.0.date'))->toBe($weekStartDate->format('Y-m-d'))
            ->and(Arr::get($result, 'props.events.calendarView.6.date'))->toBe($weekEndDate->format('Y-m-d'))
            ->and(Arr::get($result, 'props.events.calendarView.6.hasEvent'))->tobeTrue()
            ->and(Arr::get($result, 'props.events.calendarView.6.isCurrentMonth'))->tobeTrue();
    });

    it('renders Daily View of Calendar CalendarEvent list page', function (): void {
        actingAs($this->user);
        auth()->login($this->user);
        $action = new CalendarsListPage;

        $todayDate = Illuminate\Support\Facades\Date::today('Europe/Kyiv')->setTime(22, 0, 0);
        $this->data['start_date_time'] = $todayDate->copy()->setTimezone('UTC')->format('Y-m-d H:i:s');
        $this->data['date'] = $todayDate->format('Y-m-d');

        $this->dailyEvent = CalendarEvent::factory()->create($this->data);
        $this->dailyEvent->calendarEventUsers()->attach($this->user->getKey(), ['role' => CalendarEventRoleEnum::HOST, 'colour' => CalendarEventColoursEnum::BLUE->value]);

        $previousMonthDate = Illuminate\Support\Facades\Date::today('Europe/Kyiv')->startOfMonth()->subDays(10)->setTime(22, 0, 0);
        $this->data['start_date_time'] = $previousMonthDate->copy()->setTimezone('UTC')->format('Y-m-d H:i:s');
        $this->data['date'] = $previousMonthDate->format('Y-m-d');

        $this->previousMonthEvent = CalendarEvent::factory()->create($this->data);
        $this->previousMonthEvent->calendarEventUsers()->attach($this->user->getKey(), ['role' => CalendarEventRoleEnum::HOST, 'colour' => CalendarEventColoursEnum::BLUE->value]);

        $nextMonthDate = Illuminate\Support\Facades\Date::today('Europe/Kyiv')->endOfMonth()->addDays(10)->setTime(22, 0, 0);
        $this->data['start_date_time'] = $nextMonthDate->copy()->setTimezone('UTC')->format('Y-m-d H:i:s');
        $this->data['date'] = $nextMonthDate->format('Y-m-d');

        $this->nextMonthEvent = CalendarEvent::factory()->create($this->data);
        $this->nextMonthEvent->calendarEventUsers()->attach($this->user->getKey(), ['role' => CalendarEventRoleEnum::HOST, 'colour' => CalendarEventColoursEnum::BLUE->value]);

        $requestDate = Illuminate\Support\Facades\Date::now('Europe/Kyiv')->format('Y-m-d');
        $requestData = [
            'timezone' => 'Europe/Kyiv',
            'date'     => $requestDate,
            'mode'     => 'Day view',
        ];

        $request = new Request($requestData);

        $response = $action->handle($request);
        $resultData = $response->toResponse(request())->getOriginalContent();
        $result = $resultData->getData()['page'];

        $timezoneAbbreviation = $todayDate->format('T');

        expect($response)->toBeInstanceOf(Response::class)
            ->and(Arr::get($result, 'component'))->toBe('Calendar/CalendarsList')
            ->and(Arr::get($result, 'props.canLogin'))->toBeTrue()
            ->and(Arr::get($result, 'props.canRegister'))->toBeTrue()
            ->and(Arr::get($result, 'props.locale'))->toBe(app()->getLocale())
            ->and(Arr::get($result, 'props.permissions'))->toBe('edit')
            ->and(Arr::get($result, 'props.events.events.0.href'))->toBe($this->data['web_link'])
            ->and(Arr::get($result, 'props.events.events.0.dateTime'))->toBe($todayDate->format('Y-m-d').'"'.$timezoneAbbreviation.'"'.$todayDate->format('H:i:s'))
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
            ->and(Arr::get($result, 'props.events.calendarView.'.Illuminate\Support\Facades\Date::today('Europe/Kyiv')->format('Y-m')))->toBeArray()
            ->and(Arr::get($result, 'props.events.calendarView.'.Illuminate\Support\Facades\Date::today('Europe/Kyiv')->format('Y-m')))->toContainEqual([
                'date'           => Illuminate\Support\Facades\Date::today('Europe/Kyiv')->format('Y-m-d'),
                'isCurrentMonth' => true,
                'isSelected'     => true,
                'isToday'        => true,
                'hasEvent'       => true,
            ]);
    });
});
