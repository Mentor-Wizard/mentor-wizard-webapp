<?php

declare(strict_types=1);

use App\Actions\Calendar\StoreCalendarEvent;
use App\Enums\CalendarEventColoursEnum;
use App\Enums\CalendarEventRoleEnum;
use App\Enums\CalendarEventStatusEnum;
use App\Enums\CalendarEventTypeEnum;
use App\Enums\RoleEnum;
use App\Http\Requests\Calendar\StoreEventRequest;
use App\Models\CalendarEvent;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

mutates(StoreCalendarEvent::class);

describe('StoreEventRequest Validation', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        $this->user = createAndAuthenticateMentorForCalendar();

        $this->prepareRequest = function (StoreEventRequest $request): void {
            $request->setContainer(app());
            $request->setRedirector(app(Illuminate\Routing\Redirector::class));
            $request->setUserResolver(fn () => $this->user);
        };
    });

    it('validates with correct data', function (array $validData): void {
        $request = new StoreEventRequest;
        $request->merge($validData);
        ($this->prepareRequest)($request);

        expect($request->authorize())->toBeTrue();
        expect($request->rules())->toBeArray();
        expect(fn () => $request->validateResolved())->not->toThrow(ValidationException::class);
    })->with([
        'single day event' => function (): array {
            $tomorrow = Carbon::tomorrow()->format('Y-m-d');

            return [
                'title'       => 'Standup',
                'fromDate'    => $tomorrow,
                'toDate'      => $tomorrow,
                'fromTime'    => '09:00',
                'toTime'      => '10:00',
                'description' => 'Daily standup',
                'type'        => CalendarEventTypeEnum::INDIVIDUAL->value,
                'colour'      => CalendarEventColoursEnum::BLUE->value,
                'timezone'    => 'Europe/Kyiv',
            ];
        },
        'multi day event' => function (): array {
            $tomorrow = Carbon::tomorrow()->format('Y-m-d');
            $dayAfter = Carbon::tomorrow()->addDay()->format('Y-m-d');

            return [
                'title'       => 'Hackathon',
                'fromDate'    => $tomorrow,
                'toDate'      => $dayAfter,
                'fromTime'    => '09:00',
                'toTime'      => '10:00',
                'description' => 'Team building',
                'type'        => CalendarEventTypeEnum::GROUP->value,
                'colour'      => CalendarEventColoursEnum::GREEN->value,
                'timezone'    => 'Europe/Kyiv',
            ];
        },
    ]);

    it('fails validation with invalid data', function (array $invalidData, string $errorField): void {
        $request = new StoreEventRequest;
        $request->merge($invalidData);
        ($this->prepareRequest)($request);

        try {
            $request->validateResolved();
            $this->fail('Validation should have failed');
        } catch (ValidationException $validationException) {
            expect($validationException->errors())->toHaveKey($errorField);
        }
    })->with([
        'empty title' => fn (): array => [[
            'title'       => '',
            'fromDate'    => Carbon::tomorrow()->format('Y-m-d'),
            'toDate'      => Carbon::tomorrow()->format('Y-m-d'),
            'fromTime'    => '09:00',
            'toTime'      => '10:00',
            'description' => 'x',
            'type'        => CalendarEventTypeEnum::INDIVIDUAL->value,
            'colour'      => CalendarEventColoursEnum::BLUE->value,
            'timezone'    => 'Europe/Kyiv',
        ], 'title'],
        'past fromDate' => fn (): array => [[
            'title'       => 'Past date',
            'fromDate'    => Carbon::yesterday()->format('Y-m-d'),
            'toDate'      => Carbon::tomorrow()->format('Y-m-d'),
            'fromTime'    => '09:00',
            'toTime'      => '10:00',
            'description' => 'x',
            'type'        => CalendarEventTypeEnum::INDIVIDUAL->value,
            'colour'      => CalendarEventColoursEnum::BLUE->value,
            'timezone'    => 'Europe/Kyiv',
        ], 'fromDate'],
        'toTime before fromTime (same day)' => fn (): array => [[
            'title'           => 'Wrong time',
            'fromDate'        => Carbon::tomorrow()->format('Y-m-d'),
            'toDate'          => Carbon::tomorrow()->format('Y-m-d'),
            'fromTime'        => '10:00',
            'toTime'          => '09:00',
            'description'     => 'x',
            'type'            => CalendarEventTypeEnum::GROUP->value,
            'colour'          => CalendarEventColoursEnum::BLUE->value,
            'timezone'        => 'Europe/Kyiv',
        ], 'toTime'],
        'invalid type' => fn (): array => [[
            'title'       => 'Type fail',
            'fromDate'    => Carbon::tomorrow()->format('Y-m-d'),
            'toDate'      => Carbon::tomorrow()->format('Y-m-d'),
            'fromTime'    => '09:00',
            'toTime'      => '10:00',
            'description' => 'x',
            'type'        => 'Invalid',
            'colour'      => CalendarEventColoursEnum::BLUE->value,
            'timezone'    => 'Europe/Kyiv',
        ], 'type'],
    ]);
});

describe('Store Calendar CalendarEvent', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        $this->user = createAndAuthenticateMentorForCalendar();
    });

    it('stores event, attaches host and redirects to calendar page', function (): void {
        Carbon::setTestNow(Carbon::create(2025, 5, 1, 12, 0, 0, 'UTC'));
        $start = Carbon::tomorrow()->setTime(9, 0, 0);
        $end = Carbon::tomorrow()->setTime(10, 0, 0);

        $eventPayload = [
            'title'           => 'Planning',
            'status'          => CalendarEventStatusEnum::CONFIRMED->value,
            'start_date_time' => $start,
            'end_date_time'   => $end,
            'duration'        => $start->diffInSeconds($end),
            'date'            => $start->format('Y-m-d'),
            'type'            => CalendarEventTypeEnum::INDIVIDUAL->value,
            'description'     => 'Sprint planning',
            'colour'          => CalendarEventColoursEnum::BLUE->value,
        ];

        $request = Mockery::mock(StoreEventRequest::class);
        $request->shouldReceive('getEventData')->andReturn($eventPayload);
        $request->shouldReceive('user')->andReturn(Auth::user());

        $response = (new StoreCalendarEvent)->handle($request);

        expect($response)
            ->toBeInstanceOf(RedirectResponse::class)
            ->and($response->getTargetUrl())->toBe(route('pages.calendar.index'));

        expect(CalendarEvent::query()->count())->toBe(1);

        $event = CalendarEvent::query()->latest('id')->first();
        expect($event)
            ->title->toBe('Planning')
            ->status->toBe(CalendarEventStatusEnum::CONFIRMED->value)
            ->date->toBe($start->format('Y-m-d'))
            ->duration->toBe((int) $start->diffInSeconds($end));

        $pivot = $event->calendarEventUsers()
            ->where('users.id', $this->user->getKey())
            ->withPivot(['role', 'colour', 'created_at', 'updated_at'])
            ->first()?->pivot;

        expect($pivot)
            ->not->toBeNull()
            ->and($pivot->role)->toBe(CalendarEventRoleEnum::HOST->value)
            ->and($pivot->colour)->toBe(CalendarEventColoursEnum::BLUE->value);

        // Verify timestamps are explicitly set in the database
        $pivotRecord = Illuminate\Support\Facades\DB::table('calendar_event_user')
            ->where('calendar_event_id', $event->getKey())
            ->where('user_id', $this->user->getKey())
            ->first();

        expect($pivotRecord->created_at)->not->toBeNull();
        expect($pivotRecord->updated_at)->not->toBeNull();
        expect((string) Carbon::parse($pivotRecord->created_at))->toBe((string) now());
        expect((string) Carbon::parse($pivotRecord->updated_at))->toBe((string) now());
    });

    it('aborts with 403 for non-mentor user', function (): void {
        Auth::logout();
        $viewer = User::factory()->create();
        Auth::login($viewer);

        $request = Mockery::mock(StoreEventRequest::class);
        $request->shouldReceive('user')->andReturn($viewer);

        expect(fn (): RedirectResponse => (new StoreCalendarEvent)->handle($request))
            ->toThrow(Symfony\Component\HttpKernel\Exception\HttpException::class);
    });
});

function createAndAuthenticateMentorForCalendar(): User
{
    $user = User::factory()->create();
    $user->assignRole(Role::findByName(RoleEnum::MENTOR->value));
    Auth::login($user);

    return $user;
}
