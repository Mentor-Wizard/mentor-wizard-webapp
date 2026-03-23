<?php

declare(strict_types=1);

use App\Actions\Calendar\StoreCalendarEvent;
use App\Enums\CalendarEventColoursEnum;
use App\Enums\CalendarEventRoleEnum;
use App\Enums\CalendarEventStatusEnum;
use App\Enums\CalendarEventTypeEnum;
use App\Enums\MentorSessionTypeEnum;
use App\Enums\RoleEnum;
use App\Http\Requests\Calendar\StoreCalendarEventRequest;
use App\Models\CalendarEvent;
use App\Models\MentorProgram;
use App\Models\MentorSession;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

mutates(StoreCalendarEvent::class);

describe('StoreCalendarEventRequest Validation', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        $this->user = createAndAuthenticateMentorForCalendar();
        $this->mentorProgram = MentorProgram::factory()->create(['mentor_id' => $this->user->getKey()]);

        $this->prepareRequest = function (StoreCalendarEventRequest $request): void {
            $request->setContainer(app());
            $request->setRedirector(resolve(Redirector::class));
            $request->setUserResolver(fn () => $this->user);
        };
    });

    it('validates with correct data', function (array $validData): void {
        $request = new StoreCalendarEventRequest;
        $request->merge($validData);
        ($this->prepareRequest)($request);

        expect($request->authorize())->toBeTrue();
        expect($request->rules())->toBeArray();
        expect($request->validateResolved(...))->not->toThrow(ValidationException::class);
    })->with([
        'single day event' => function (): array {
            $tomorrow = Date::tomorrow()->format('Y-m-d');

            return [
                'title'             => 'Standup',
                'fromDate'          => $tomorrow,
                'toDate'            => $tomorrow,
                'fromTime'          => '09:00',
                'toTime'            => '10:00',
                'description'       => 'Daily standup',
                'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
                'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION->value,
                'colour'            => CalendarEventColoursEnum::BLUE->value,
                'mentor_program_id' => $this->mentorProgram->id,
            ];
        },
        'multi day event' => function (): array {
            $tomorrow = Date::tomorrow()->format('Y-m-d');
            $dayAfter = Date::tomorrow()->addDay()->format('Y-m-d');

            return [
                'title'             => 'Hackathon',
                'fromDate'          => $tomorrow,
                'toDate'            => $dayAfter,
                'fromTime'          => '09:00',
                'toTime'            => '10:00',
                'description'       => 'Team building',
                'type'              => CalendarEventTypeEnum::GROUP->value,
                'session_type'      => MentorSessionTypeEnum::VOICE_SESSION->value,
                'colour'            => CalendarEventColoursEnum::GREEN->value,
                'mentor_program_id' => $this->mentorProgram->id,
            ];
        },
    ]);

    it('fails validation with invalid data', function (array $invalidData, string $errorField): void {
        $request = new StoreCalendarEventRequest;
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
            'title'             => '',
            'fromDate'          => Date::tomorrow()->format('Y-m-d'),
            'toDate'            => Date::tomorrow()->format('Y-m-d'),
            'fromTime'          => '09:00',
            'toTime'            => '10:00',
            'description'       => 'x',
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'colour'            => CalendarEventColoursEnum::BLUE->value,
            'timezone'          => 'Europe/Kyiv',
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ], 'title'],
        'past fromDate' => fn (): array => [[
            'title'             => 'Past date',
            'fromDate'          => Date::yesterday()->format('Y-m-d'),
            'toDate'            => Date::tomorrow()->format('Y-m-d'),
            'fromTime'          => '09:00',
            'toTime'            => '10:00',
            'description'       => 'x',
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'colour'            => CalendarEventColoursEnum::BLUE->value,
            'timezone'          => 'Europe/Kyiv',
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ], 'fromDate'],
        'toTime before fromTime (same day)' => fn (): array => [[
            'title'             => 'Wrong time',
            'fromDate'          => Date::tomorrow()->format('Y-m-d'),
            'toDate'            => Date::tomorrow()->format('Y-m-d'),
            'fromTime'          => '10:00',
            'toTime'            => '09:00',
            'description'       => 'x',
            'type'              => CalendarEventTypeEnum::GROUP->value,
            'colour'            => CalendarEventColoursEnum::BLUE->value,
            'timezone'          => 'Europe/Kyiv',
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ], 'toTime'],
        'invalid type' => fn (): array => [[
            'title'             => 'Type fail',
            'fromDate'          => Date::tomorrow()->format('Y-m-d'),
            'toDate'            => Date::tomorrow()->format('Y-m-d'),
            'fromTime'          => '09:00',
            'toTime'            => '10:00',
            'description'       => 'x',
            'type'              => 'Invalid',
            'colour'            => CalendarEventColoursEnum::BLUE->value,
            'timezone'          => 'Europe/Kyiv',
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ], 'type'],
    ]);
});

describe('Store Calendar CalendarEvent', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        $this->user = createAndAuthenticateMentorForCalendar();
        $this->mentorProgram = MentorProgram::factory()->create(['mentor_id' => $this->user->getKey()]);
    });

    it('stores event, attaches host and redirects to calendar page', function (): void {
        Date::setTestNow(Date::create(2025, 5, 1, 12, 0, 0, config('app.timezone')));
        $start = Date::tomorrow()->setTime(9, 0, 0);
        $end = Date::tomorrow()->setTime(10, 0, 0);

        $eventPayload = [
            'title'             => 'Planning',
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'fromDate'          => $start->format('Y-m-d'),
            'toDate'            => $end->format('Y-m-d'),
            'fromTime'          => '09:00',
            'toTime'            => '10:00',
            'webLink'           => 'https://google.com',
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION->value,
            'description'       => 'Sprint planning',
            'colour'            => CalendarEventColoursEnum::BLUE->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ];

        $request = Mockery::mock(StoreCalendarEventRequest::class);
        $request->shouldReceive('validated')->andReturn($eventPayload);
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
            ->date->toBe($start->format('Y-m-d'));

        $pivot = $event->calendarEventUsers()
            ->where('users.id', $this->user->getKey())
            ->withPivot(['role', 'colour', 'created_at', 'updated_at'])
            ->first()?->pivot;

        expect($pivot)
            ->not->toBeNull()
            ->and($pivot->role)->toBe(CalendarEventRoleEnum::HOST->value)
            ->and($pivot->colour)->toBe(CalendarEventColoursEnum::BLUE->value);

        // Verify timestamps are explicitly set in the database
        $pivotRecord = DB::table('calendar_event_user')
            ->where('calendar_event_id', $event->getKey())
            ->where('user_id', $this->user->getKey())
            ->first();

        expect($pivotRecord->created_at)->not->toBeNull();
        expect($pivotRecord->updated_at)->not->toBeNull();
        expect((string) Date::parse($pivotRecord->created_at))->toBe((string) now());
        expect((string) Date::parse($pivotRecord->updated_at))->toBe((string) now());
    });

    it('stores event with several users', function (): void {
        Date::setTestNow(Date::create(2025, 5, 1, 12, 0, 0, config('app.timezone')));
        $start = Date::tomorrow()->setTime(9, 0, 0);
        $end = Date::tomorrow()->setTime(10, 0, 0);

        $nonMentor = User::factory()->create();
        $nonMentor->assignRole(RoleEnum::MENTI);
        Auth::login($nonMentor);

        $eventPayload = [
            'title'             => 'Planning',
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'fromDate'          => $start->format('Y-m-d'),
            'toDate'            => $end->format('Y-m-d'),
            'fromTime'          => '09:00',
            'toTime'            => '10:00',
            'webLink'           => 'https://google.com',
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION->value,
            'description'       => 'Sprint planning',
            'colour'            => CalendarEventColoursEnum::BLUE->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ];

        $request = Mockery::mock(StoreCalendarEventRequest::class);
        $request->shouldReceive('validated')->andReturn($eventPayload);
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
            ->date->toBe($start->format('Y-m-d'));

        $attachedUsers = $event->calendarEventUsers()
            ->withPivot(['role', 'colour', 'created_at', 'updated_at'])
            ->get();

        // Should have 2 users: the mentor (HOST) and the non-mentor (PARTICIPANT)
        expect($attachedUsers)->toHaveCount(2);

        $host = $attachedUsers->firstWhere('id', $this->user->getKey());
        $menti = $attachedUsers->firstWhere('id', $nonMentor->getKey());

        expect($host)
            ->not->toBeNull()
            ->and($host->pivot->role)->toBe(CalendarEventRoleEnum::HOST->value)
            ->and($host->pivot->colour)->toBe(CalendarEventColoursEnum::BLUE->value);

        expect($menti)
            ->not->toBeNull()
            ->and($menti->pivot->role)->toBe(CalendarEventRoleEnum::PARTICIPANT->value)
            ->and($menti->pivot->colour)->toBe(CalendarEventColoursEnum::BLUE->value);

        $mentorSession = MentorSession::query()->latest('id')->first();
        expect($mentorSession)
            ->not->toBeNull()
            ->and($mentorSession->mentor_id)->toBe($this->user->getKey())
            ->and($mentorSession->menti_id)->toBe($nonMentor->getKey())
            ->and($mentorSession->mentor_program_id)->toBe($this->mentorProgram->getKey());

        $event = CalendarEvent::query()->latest('id')->first();
        expect($event->mentor_session_id)->toBe($mentorSession->getKey());
    });

    it('stores event when mentor books their own program - attaches only mentor as HOST', function (): void {
        Date::setTestNow(Date::create(2025, 5, 1, 12, 0, 0, config('app.timezone')));
        $start = Date::tomorrow()->setTime(9, 0, 0);
        $end = Date::tomorrow()->setTime(10, 0, 0);

        // Mentor is already authenticated and owns the mentor program in beforeEach
        $eventPayload = [
            'title'             => 'Self Booking',
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'fromDate'          => $start->format('Y-m-d'),
            'toDate'            => $end->format('Y-m-d'),
            'fromTime'          => '09:00',
            'toTime'            => '10:00',
            'webLink'           => 'https://google.com',
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION->value,
            'description'       => 'Self planning',
            'colour'            => CalendarEventColoursEnum::RED->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ];

        $request = Mockery::mock(StoreCalendarEventRequest::class);
        $request->shouldReceive('validated')->andReturn($eventPayload);
        $request->shouldReceive('user')->andReturn(Auth::user());

        $response = (new StoreCalendarEvent)->handle($request);

        expect($response)
            ->toBeInstanceOf(RedirectResponse::class)
            ->and($response->getTargetUrl())->toBe(route('pages.calendar.index'));

        $event = CalendarEvent::query()->latest('id')->first();
        $attachedUsers = $event->calendarEventUsers()
            ->withPivot(['role', 'colour'])
            ->get();

        // Should have only 1 user: the mentor as HOST (since mentor_id === auth user)
        expect($attachedUsers)->toHaveCount(2);

        $host = $attachedUsers->first();
        expect($host->id)->toBe($this->user->getKey())
            ->and($host->pivot->role)->toBe(CalendarEventRoleEnum::HOST->value)
            ->and($host->pivot->colour)->toBe(CalendarEventColoursEnum::RED->value);
    });

    it('throws exception when user profile has no timezone', function (): void {
        Date::setTestNow(Date::create(2025, 5, 1, 12, 0, 0, config('app.timezone')));
        $start = Date::tomorrow()->setTime(9, 0, 0);
        $end = Date::tomorrow()->setTime(10, 0, 0);

        $userWithoutTimezone = User::factory()->create();
        $userWithoutTimezone->assignRole(RoleEnum::MENTOR);
        Auth::login($userWithoutTimezone);

        DB::table('user_profiles')->where('user_id', $userWithoutTimezone->getKey())->delete();
        $userWithoutTimezone->unsetRelation('profile');

        $mentorProgram = MentorProgram::factory()->create(['mentor_id' => $userWithoutTimezone->getKey()]);

        $eventPayload = [
            'title'             => 'Test Event',
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'fromDate'          => $start->format('Y-m-d'),
            'toDate'            => $end->format('Y-m-d'),
            'fromTime'          => '09:00',
            'toTime'            => '10:00',
            'webLink'           => 'https://google.com',
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION->value,
            'description'       => 'Test description',
            'colour'            => CalendarEventColoursEnum::BLUE->value,
            'mentor_program_id' => $mentorProgram->getKey(),
        ];

        $request = Mockery::mock(StoreCalendarEventRequest::class);
        $request->shouldReceive('validated')->andReturn($eventPayload);
        $request->shouldReceive('user')->andReturn(Auth::user());

        expect(fn (): RedirectResponse => (new StoreCalendarEvent)->handle($request))
            ->toThrow(ErrorException::class, 'Attempt to read property "timezone" on null');
    });

});

function createAndAuthenticateMentorForCalendar(): User
{
    $user = User::factory()->create();
    $user->assignRole(Role::findByName(RoleEnum::MENTOR->value));

    $userMenti = User::factory()->create();
    $userMenti->assignRole(Role::findByName(RoleEnum::MENTI->value));
    Auth::login($userMenti);

    return $user;
}
