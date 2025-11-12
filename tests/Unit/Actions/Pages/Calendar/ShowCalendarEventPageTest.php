<?php

declare(strict_types=1);

use App\Actions\Pages\Calendar\ShowCalendarEventPage;
use App\Enums\CalendarEventStatusEnum;
use App\Enums\CalendarEventTypeEnum;
use App\Enums\RoleEnum;
use App\Models\CalendarEvent as EventModel;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Inertia\Response;
use Spatie\Permission\Models\Role;

mutates(ShowCalendarEventPage::class);

describe('Show Calendar CalendarEvent Page', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);

        $this->mentor = User::factory()->create();
        $this->mentor->assignRole(Role::findByName(RoleEnum::MENTOR->value));

        $this->viewer = User::factory()->create();

        $this->start = Illuminate\Support\Facades\Date::parse(Illuminate\Support\Facades\Date::today()->addDays(1)->format('Y-m-d').' 09:30:00');
        $this->end = Illuminate\Support\Facades\Date::parse(Illuminate\Support\Facades\Date::today()->addDays(1)->format('Y-m-d').' 11:00:00');

        $this->event = EventModel::factory()->create([
            'title'             => 'Demo CalendarEvent',
            'status'            => CalendarEventStatusEnum::CONFIRMED,
            'start_date_time'   => $this->start->format('Y-m-d H:i:s'),
            'end_date_time'     => $this->end->format('Y-m-d H:i:s'),
            'date'              => $this->start->format('Y-m-d'),
            'duration'          => $this->start->diffInSeconds($this->end),
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'web_link'          => 'https://example.com/meet',
            'description'       => 'CalendarEvent description',
        ]);
    });

    it('renders ShowEditEvent component with mentor permissions and correct event payload', function (): void {
        auth()->login($this->mentor);

        $request = new Request(['timezone' => 'UTC']);
        $response = new ShowCalendarEventPage()->handle($this->event, $request);
        $resultData = $response->toResponse(request())->getOriginalContent();
        $page = $resultData->getData()['page'];

        $expectedDuration = '01:30';

        expect($response)->toBeInstanceOf(Response::class)
            ->and(Arr::get($page, 'component'))->toBe('Calendar/ShowEditEvent')
            ->and(Arr::get($page, 'props.canLogin'))->toBeTrue()
            ->and(Arr::get($page, 'props.canRegister'))->toBeTrue()
            ->and(Arr::get($page, 'props.locale'))->toBe(app()->getLocale())
            ->and(Arr::get($page, 'props.permissions'))->toBe('edit')
            // CalendarEvent payload is returned as an object via EventShowResource
            ->and(Arr::get($page, 'props.event.id'))->toBe($this->event->getKey())
            ->and(Arr::get($page, 'props.event.title'))->toBe('Demo CalendarEvent')
            ->and(Arr::get($page, 'props.event.fromDate'))->toBe($this->start->format('Y-m-d'))
            ->and(Arr::get($page, 'props.event.fromDateFormatted'))->toBe($this->start->format('Y-M-d'))
            ->and(Arr::get($page, 'props.event.fromTime'))->toBe('09:30')
            ->and(Arr::get($page, 'props.event.toDate'))->toBe($this->end->format('Y-m-d'))
            ->and(Arr::get($page, 'props.event.toDateFormatted'))->toBe($this->end->format('Y-M-d'))
            ->and(Arr::get($page, 'props.event.toTime'))->toBe('11:00')
            ->and(Arr::get($page, 'props.event.duration'))->toBe($expectedDuration)
            ->and(Arr::get($page, 'props.event.href'))->toBe('https://example.com/meet')
            ->and(Arr::get($page, 'props.event.description'))->toBe('CalendarEvent description');
    });

    it('renders ShowEditEvent component with viewer permissions for non-mentor users', function (): void {
        auth()->login($this->viewer);

        $request = new Request(['timezone' => 'UTC']);
        $response = new ShowCalendarEventPage()->handle($this->event, $request);
        $resultData = $response->toResponse(request())->getOriginalContent();
        $page = $resultData->getData()['page'];

        expect($response)->toBeInstanceOf(Response::class)
            ->and(Arr::get($page, 'component'))->toBe('Calendar/ShowEditEvent')
            ->and(Arr::get($page, 'props.permissions'))->toBe('view')
            ->and(Arr::get($page, 'props.event.id'))->toBe($this->event->getKey());
    });
});
