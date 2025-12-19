<?php

declare(strict_types=1);

use App\Actions\Calendar\EditCalendarEvent;
use App\Enums\CalendarEventColoursEnum;
use App\Enums\CalendarEventRoleEnum;
use App\Enums\CalendarEventStatusEnum;
use App\Enums\CalendarEventTypeEnum;
use App\Enums\RoleEnum;
use App\Http\Requests\Calendar\EditCalendarEventRequest;
use App\Models\CalendarEvent;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\Response;

mutates(EditCalendarEvent::class);

describe('EditCalendarEventRequest Validation', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        $this->user = createAndAuthenticateMentorForCalendarUpdate();

        $this->prepareRequest = function (EditCalendarEventRequest $request): void {
            $request->setContainer(app());
            $request->setRedirector(app(Redirector::class));
            $request->setUserResolver(fn () => $this->user);
        };
    });

    it('validates with correct data', function (array $validData): void {
        $request = new EditCalendarEventRequest;
        $request->merge($validData);
        ($this->prepareRequest)($request);

        expect($request->authorize())->toBeTrue();
        expect($request->rules())->toBeArray();
        expect($request->validateResolved(...))->not->toThrow(ValidationException::class);
    })->with([
        'single day event' => function (): array {
            $tomorrow = Date::tomorrow()->format('Y-m-d');

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
        $request = new EditCalendarEventRequest;
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
            'fromDate'    => Date::tomorrow()->format('Y-m-d'),
            'toDate'      => Date::tomorrow()->format('Y-m-d'),
            'fromTime'    => '09:00',
            'toTime'      => '10:00',
            'description' => 'x',
            'type'        => CalendarEventTypeEnum::INDIVIDUAL->value,
            'timezone'    => 'Europe/Kyiv',
        ], 'title'],
        'toTime before fromTime' => fn (): array => [[
            'title'       => 'Wrong time',
            'fromDate'    => Date::tomorrow()->format('Y-m-d'),
            'toDate'      => Date::tomorrow()->format('Y-m-d'),
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

        $this->event = CalendarEvent::factory()->create([
            'title'             => 'Default event',
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'start_date_time'   => Date::tomorrow()->format('Y-m-d').' 09:00:00',
            'date'              => Date::tomorrow()->format('Y-m-d'),
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'description'       => 'Test description',
            'mentor_program_id' => null,
        ]);
        $this->event->calendarEventUsers()->attach($this->user->getKey(), ['role' => CalendarEventRoleEnum::HOST]);
    });

    it('updates event with valid data and redirects', function (): void {
        $start = Date::tomorrow()->setTime(13, 0, 0);
        $end = Date::tomorrow()->setTime(14, 30, 0);
        $payload = [
            'id'              => $this->event->getKey(),
            'title'           => 'Updated Title',
            'status'          => CalendarEventStatusEnum::CONFIRMED->value,
            'fromDate'        => $start->format('Y-m-d'),
            'toDate'          => $end->format('Y-m-d'),
            'fromTime'        => '13:00',
            'toTime'          => '14:00',
            'type'            => CalendarEventTypeEnum::INDIVIDUAL->value,
            'colour'          => CalendarEventColoursEnum::BLUE->value,
            'description'     => 'Updated description',
        ];

        $request = Mockery::mock(EditCalendarEventRequest::class);
        $request->shouldReceive('validated')->andReturn($payload);
        $request->shouldReceive('user')->andReturn(Auth::user());

        $response = (new EditCalendarEvent)->handle($request, $this->event);

        expect($response)
            ->toBeInstanceOf(Response::class)
            ->and($response->getTargetUrl())->toBe(route('pages.calendar.index'));

        $updated = CalendarEvent::query()->whereKey($this->event->getKey())->first();
        expect($updated)
            ->title->toBe('Updated Title')
            ->date->toBe($start->format('Y-m-d'))
            ->type->toBe(CalendarEventTypeEnum::INDIVIDUAL->value)
            ->description->toBe('Updated description');
    });

    it('returns 403 for non-mentor user', function (): void {
        Auth::logout();
        $viewer = User::factory()->create();
        Auth::login($viewer);
        $this->actingAs($viewer);

        $request = Mockery::mock(EditCalendarEventRequest::class);
        $request->shouldReceive('getEventData')->never();

        $response = $this->patch(route('pages.calendar.edit', $this->event), [
            'title' => 'Updated Event',
        ]);

        $response->assertStatus(403);
    });

    it('syncs user colour correctly', function (): void {
        $start = Date::tomorrow()->setTime(13, 0, 0);
        $end = Date::tomorrow()->setTime(14, 30, 0);
        $payload = [
            'title'           => 'Updated Title',
            'status'          => CalendarEventStatusEnum::CONFIRMED->value,
            'fromDate'        => $start->format('Y-m-d'),
            'toDate'          => $end->format('Y-m-d'),
            'fromTime'        => '13:00',
            'toTime'          => '14:00',
            'type'            => CalendarEventTypeEnum::GROUP->value,
            'colour'          => CalendarEventColoursEnum::GREEN->value,
            'description'     => 'Updated description',
        ];

        $request = Mockery::mock(EditCalendarEventRequest::class);
        $request->shouldReceive('validated')->andReturn($payload);
        $request->shouldReceive('user')->andReturn(Auth::user());

        (new EditCalendarEvent)->handle($request, $this->event);

        // Verify colour was synced to pivot table
        $pivot = $this->event->calendarEventUsers()->where('user_id', $this->user->getKey())->first()?->pivot;
        expect($pivot)->not->toBeNull()
            ->and($pivot->colour)->toBe(CalendarEventColoursEnum::GREEN->value);
    });
});

function createAndAuthenticateMentorForCalendarUpdate(): User
{
    $user = User::factory()->create();
    $user->assignRole(Role::findByName(RoleEnum::MENTOR->value));
    Auth::login($user);

    return $user;
}
