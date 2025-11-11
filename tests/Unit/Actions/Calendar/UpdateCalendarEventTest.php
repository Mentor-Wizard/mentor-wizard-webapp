<?php

declare(strict_types=1);

use App\Actions\Calendar\EditCalendarEvent;
use App\Enums\CalendarEventColoursEnum;
use App\Enums\CalendarEventRoleEnum;
use App\Enums\CalendarEventStatusEnum;
use App\Enums\CalendarEventTypeEnum;
use App\Enums\RoleEnum;
use App\Http\Requests\Calendar\EditEventRequest;
use App\Models\CalendarEvent as EventModel;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\Response;

mutates(EditCalendarEvent::class);

describe('EditEventRequest Validation', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        $this->user = createAndAuthenticateMentorForCalendarUpdate();

        $this->prepareRequest = function (EditEventRequest $request): void {
            $request->setContainer(app());
            $request->setRedirector(app(Illuminate\Routing\Redirector::class));
            $request->setUserResolver(fn () => $this->user);
        };
    });

    it('validates with correct data', function (array $validData): void {
        $request = new EditEventRequest;
        $request->merge($validData);
        ($this->prepareRequest)($request);

        expect($request->authorize())->toBeTrue();
        expect($request->rules())->toBeArray();
        expect(fn () => $request->validateResolved())->not->toThrow(ValidationException::class);
    })->with([
        'single day event' => function (): array {
            $tomorrow = Carbon::tomorrow()->format('Y-m-d');

            return [
                'title'       => 'Updated Standup',
                'fromDate'    => $tomorrow,
                'toDate'      => $tomorrow,
                'fromTime'    => '11:00',
                'toTime'      => '12:00',
                'description' => 'Updated desc',
                'type'        => CalendarEventTypeEnum::INDIVIDUAL->value,
                'colour'      => CalendarEventColoursEnum::BLUE->value,
                'timezone'    => 'Europe/Kyiv',
            ];
        },
    ]);

    it('fails validation with invalid data', function (array $invalidData, string $errorField): void {
        $request = new EditEventRequest;
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
            'timezone'    => 'Europe/Kyiv',
        ], 'title'],
        'toTime before fromTime' => fn (): array => [[
            'title'       => 'Wrong time',
            'fromDate'    => Carbon::tomorrow()->format('Y-m-d'),
            'toDate'      => Carbon::tomorrow()->format('Y-m-d'),
            'fromTime'    => '10:00',
            'toTime'      => '09:00',
            'description' => 'x',
            'type'        => CalendarEventTypeEnum::GROUP->value,
            'timezone'    => 'Europe/Kyiv',
        ], 'toTime'],
    ]);
});

describe('Update Calendar CalendarEvent', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        $this->user = createAndAuthenticateMentorForCalendarUpdate();

        $this->event = EventModel::factory()->create([
            'title'             => 'Default event',
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'start_date_time'   => Carbon::tomorrow()->format('Y-m-d').' 09:00:00',
            'date'              => Carbon::tomorrow()->format('Y-m-d'),
            'duration'          => 3600,
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'description'       => 'Test description',
            'mentor_program_id' => null,
        ]);
        $this->event->calendarEventUsers()->attach($this->user->getKey(), ['role' => CalendarEventRoleEnum::HOST]);
    });

    it('updates event with valid data and redirects', function (): void {
        $start = Carbon::tomorrow()->setTime(13, 0, 0);
        $end = Carbon::tomorrow()->setTime(14, 30, 0);
        $payload = [
            'title'           => 'Updated Title',
            'status'          => CalendarEventStatusEnum::CONFIRMED->value,
            'start_date_time' => $start,
            'end_date_time'   => $end,
            'duration'        => $start->diffInSeconds($end),
            'date'            => $start->format('Y-m-d'),
            'type'            => CalendarEventTypeEnum::GROUP->value,
            'colour'          => CalendarEventColoursEnum::BLUE->value,
            'description'     => 'Updated description',
        ];

        $request = Mockery::mock(EditEventRequest::class);
        $request->shouldReceive('getEventData')->andReturn($payload);
        $request->shouldReceive('user')->andReturn(Auth::user());

        $response = (new EditCalendarEvent)->handle($request, $this->event);

        expect($response)
            ->toBeInstanceOf(Response::class)
            ->and($response->getTargetUrl())->toBe(route('pages.calendar.index'));

        $updated = EventModel::query()->whereKey($this->event->getKey())->first();
        expect($updated)
            ->title->toBe('Updated Title')
            ->date->toBe($start->format('Y-m-d'))
            ->duration->toBe((int) $start->diffInSeconds($end))
            ->type->toBe(CalendarEventTypeEnum::GROUP->value)
            ->description->toBe('Updated description');
    });

    it('returns 403 for non-mentor user', function (): void {
        Auth::logout();
        $viewer = User::factory()->create();
        Auth::login($viewer);
        $this->actingAs($viewer);

        $request = Mockery::mock(EditEventRequest::class);
        $request->shouldReceive('getEventData')->never();

        $response = $this->patch(route('pages.calendar.edit', $this->event), [
            'title' => 'Updated Event',
        ]);

        $response->assertStatus(419);
    });
});

function createAndAuthenticateMentorForCalendarUpdate(): User
{
    $user = User::factory()->create();
    $user->assignRole(Role::findByName(RoleEnum::MENTOR->value));
    Auth::login($user);

    return $user;
}
