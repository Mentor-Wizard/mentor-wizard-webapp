<?php

declare(strict_types=1);

use App\Actions\Pages\Calendar\ShowCalendarEventPage;
use App\Enums\EventStatusEnum;
use App\Enums\EventTypeEnum;
use App\Enums\RoleEnum;
use App\Models\User;
use App\Models\Event as EventModel;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Carbon;
use Inertia\Response;
use Spatie\Permission\Models\Role;

mutates(ShowCalendarEventPage::class);

describe('Show Calendar Event Page', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);

        $this->mentor = User::factory()->create();
        $this->mentor->assignRole(Role::findByName(RoleEnum::MENTOR->value));

        $this->viewer = User::factory()->create();

        $this->start = Carbon::parse(Carbon::today()->addDays(1)->format('Y-m-d') . ' 09:30:00');
        $this->end = Carbon::parse(Carbon::today()->addDays(1)->format('Y-m-d') . ' 11:00:00');

        $this->event = EventModel::factory()->create([
            'title'             => 'Demo Event',
            'status'            => EventStatusEnum::CONFIRMED,
            'start_date_time'   => $this->start->format('Y-m-d H:i:s'),
            'end_date_time'     => $this->end->format('Y-m-d H:i:s'),
            'date'              => $this->start->format('Y-m-d'),
            'duration'          => $this->start->diffInSeconds($this->end),
            'type'              => EventTypeEnum::INDIVIDUAL->value,
            'web_link'          => 'https://example.com/meet',
            'description'       => 'Event description',
        ]);
    });

    it('renders ShowEditEvent component with mentor permissions and correct event payload', function (): void {
        auth()->login($this->mentor);

        $response = new ShowCalendarEventPage()->handle($this->event->unique_id);
        $resultData = $response->toResponse(request())->getOriginalContent();
        $page = $resultData->getData()['page'];

        $expectedDuration = '01:30';

        expect($response)->toBeInstanceOf(Response::class)
            ->and(Arr::get($page, 'component'))->toBe('Calendar/ShowEditEvent')
            ->and(Arr::get($page, 'props.canLogin'))->toBeTrue()
            ->and(Arr::get($page, 'props.canRegister'))->toBeTrue()
            ->and(Arr::get($page, 'props.locale'))->toBe(app()->getLocale())
            ->and(Arr::get($page, 'props.permissions'))->toBe('edit')
            // Event payload is returned as an array via collection()->resolve()
            ->and(Arr::get($page, 'props.event.0.id'))->toBe($this->event->unique_id)
            ->and(Arr::get($page, 'props.event.0.title'))->toBe('Demo Event')
            ->and(Arr::get($page, 'props.event.0.fromDate'))->toBe($this->start->format('Y-m-d'))
            ->and(Arr::get($page, 'props.event.0.fromDateFormatted'))->toBe($this->start->format('Y-M-d'))
            ->and(Arr::get($page, 'props.event.0.fromTime'))->toBe('09:30')
            ->and(Arr::get($page, 'props.event.0.toDate'))->toBe($this->end->format('Y-m-d'))
            ->and(Arr::get($page, 'props.event.0.toDateFormatted'))->toBe($this->end->format('Y-M-d'))
            ->and(Arr::get($page, 'props.event.0.toTime'))->toBe('11:00')
            ->and(Arr::get($page, 'props.event.0.duration'))->toBe($expectedDuration)
            ->and(Arr::get($page, 'props.event.0.href'))->toBe('https://example.com/meet')
            ->and(Arr::get($page, 'props.event.0.description'))->toBe('Event description');
    });

    it('renders ShowEditEvent component with viewer permissions for non-mentor users', function (): void {
        auth()->login($this->viewer);

        $response = new ShowCalendarEventPage()->handle($this->event->unique_id);
        $resultData = $response->toResponse(request())->getOriginalContent();
        $page = $resultData->getData()['page'];

        expect($response)->toBeInstanceOf(Response::class)
            ->and(Arr::get($page, 'component'))->toBe('Calendar/ShowEditEvent')
            ->and(Arr::get($page, 'props.permissions'))->toBe('view')
            ->and(Arr::get($page, 'props.event.0.id'))->toBe($this->event->unique_id);
    });

    it('returns empty event array for non-existing uuid', function (): void {
        auth()->login($this->mentor);

        $response = new ShowCalendarEventPage()->handle((string) str()->uuid());
        $resultData = $response->toResponse(request())->getOriginalContent();
        $page = $resultData->getData()['page'];

        expect($response)->toBeInstanceOf(Response::class)
            ->and(Arr::get($page, 'component'))->toBe('Calendar/ShowEditEvent')
            ->and(Arr::get($page, 'props.event'))->toBeArray()
            ->and(Arr::get($page, 'props.event'))->toHaveCount(0);
    });
});
