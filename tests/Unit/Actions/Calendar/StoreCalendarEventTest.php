<?php

declare(strict_types=1);

use App\Actions\Calendar\StoreCalendarPage;
use App\Enums\EventRoleEnum;
use App\Enums\EventStatusEnum;
use App\Enums\EventTypeEnum;
use App\Enums\RoleEnum;
use App\Http\Requests\Calendar\StoreEventRequest;
use App\Models\Event;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\Response;

mutates(StoreCalendarPage::class);

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
                'title' => 'Standup',
                'fromDate' => $tomorrow,
                'toDate' => $tomorrow,
                'fromTime' => '09:00',
                'toTime' => '10:00',
                'description' => 'Daily standup',
                // Must match Rule::in(EventTypeEnum::values())
                'type' => EventTypeEnum::INDIVIDUAL->value,
            ];
        },
        'multi day event' => function (): array {
            $tomorrow = Carbon::tomorrow()->format('Y-m-d');
            $dayAfter = Carbon::tomorrow()->addDay()->format('Y-m-d');
            return [
                'title' => 'Hackathon',
                'fromDate' => $tomorrow,
                'toDate' => $dayAfter,
                'fromTime' => '09:00',
                'toTime' => '10:00',
                'description' => 'Team building',
                'type' => EventTypeEnum::GROUP->value,
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
            'title' => '',
            'fromDate' => Carbon::tomorrow()->format('Y-m-d'),
            'toDate' => Carbon::tomorrow()->format('Y-m-d'),
            'fromTime' => '09:00',
            'toTime' => '10:00',
            'description' => 'x',
            'type' => EventTypeEnum::INDIVIDUAL->value,
        ], 'title'],
        'past fromDate' => fn (): array => [[
            'title' => 'Past date',
            'fromDate' => Carbon::yesterday()->format('Y-m-d'),
            'toDate' => Carbon::tomorrow()->format('Y-m-d'),
            'fromTime' => '09:00',
            'toTime' => '10:00',
            'description' => 'x',
            'type' => EventTypeEnum::INDIVIDUAL->value,
        ], 'fromDate'],
        'toTime before fromTime (same day)' => fn (): array => [[
            'title' => 'Wrong time',
            'fromDate' => Carbon::tomorrow()->format('Y-m-d'),
            'toDate' => Carbon::tomorrow()->format('Y-m-d'),
            'fromTime' => '10:00',
            'toTime' => '09:00',
            'description' => 'x',
            'type' => EventTypeEnum::GROUP->value,
        ], 'toTime'],
        'invalid type' => fn (): array => [[
            'title' => 'Type fail',
            'fromDate' => Carbon::tomorrow()->format('Y-m-d'),
            'toDate' => Carbon::tomorrow()->format('Y-m-d'),
            'fromTime' => '09:00',
            'toTime' => '10:00',
            'description' => 'x',
            'type' => 'Invalid',
        ], 'type'],
    ]);
});

describe('Store Calendar Event', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        $this->user = createAndAuthenticateMentorForCalendar();
    });

    it('stores event, attaches host and redirects to calendar page', function (): void {
        $start = Carbon::tomorrow()->setTime(9, 0, 0);
        $end = Carbon::tomorrow()->setTime(10, 0, 0);

        $eventPayload = [
            'unique_id' => (string) str()->uuid(),
            'title' => 'Planning',
            'status' => EventStatusEnum::CONFIRMED->value,
            'start_date_time' => $start,
            'end_date_time' => $end,
            'duration' => $start->diffInSeconds($end),
            'date' => $start->format('Y-m-d'),
            'type' => EventTypeEnum::INDIVIDUAL->value,
            'description' => 'Sprint planning',
        ];

        $request = Mockery::mock(StoreEventRequest::class);
        $request->shouldReceive('getEventData')->andReturn($eventPayload);
        $request->shouldReceive('user')->andReturn(Auth::user());

        $response = (new StoreCalendarPage)->handle($request);

        expect($response)
            ->toBeInstanceOf(Response::class)
            ->and($response->getTargetUrl())->toBe(route('pages.calendar'));

        expect(Event::query()->count())->toBe(1);

        $event = Event::query()->latest('id')->first();
        expect($event)
            ->title->toBe('Planning')
            ->status->toBe(EventStatusEnum::CONFIRMED->value)
            ->date->toBe($start->format('Y-m-d'))
            ->duration->toBe((int)$start->diffInSeconds($end));

        $attached = $event->users()
            ->where('users.id', $this->user->getKey())
            ->wherePivot('role', EventRoleEnum::HOST->value)
            ->exists();

        expect($attached)->toBeTrue();
    });
});

function createAndAuthenticateMentorForCalendar(): User
{
    $user = User::factory()->create();
    $user->assignRole(Role::findByName(RoleEnum::MENTOR->value));
    Auth::login($user);

    return $user;
}
