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
use App\Models\MentorProgram;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\Response;

mutates(EditCalendarEvent::class);

describe('EditCalendarEventRequest Validation (web link only)', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        $this->user = createAndAuthenticateMentorForCalendarUpdate();

        $this->prepareRequest = function (EditCalendarEventRequest $request): void {
            $request->setContainer(app());
            $request->setRedirector(resolve(Redirector::class));
            $request->setUserResolver(fn () => $this->user);
        };
    });

    it('validates with correct web link data', function (array $validData): void {
        $request = new EditCalendarEventRequest;
        $request->merge($validData);
        ($this->prepareRequest)($request);

        expect($request->authorize())->toBeTrue();
        expect($request->rules())->toBeArray();
        try {
            $request->validateResolved();
        } catch (ValidationException $validationException) {
            dump($validationException->errors()); // <-- see which fields are failing
            throw $validationException;
        }

        expect($request->validateResolved(...))->not->toThrow(ValidationException::class);
    })->with([
        'valid url' => fn (): array => [
            'title'             => 'Planning',
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'fromDate'          => Date::now()->addDays(3)->format('Y-m-d'),
            'toDate'            => Date::now()->addDays(3)->format('Y-m-d'),
            'fromTime'          => '09:00',
            'toTime'            => '10:00',
            'webLink'           => 'https://google.com',
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'description'       => 'Sprint planning',
            'colour'            => CalendarEventColoursEnum::BLUE->value,
            'mentor_program_id' => MentorProgram::factory()->create()->getKey(),
        ],
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
        'invalid url' => fn (): array => [[
            'webLink' => 'not-a-url',
        ], 'webLink'],
    ]);
});

describe('Update Calendar CalendarEvent web link (mentor only)', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        $this->user = createAndAuthenticateMentorForCalendarUpdate();

        // Create mentor program for current user (mentor)
        $this->program = MentorProgram::factory()->create([
            'mentor_id' => $this->user->getKey(),
        ]);

        $this->event = CalendarEvent::factory()->create([
            'date'              => Date::tomorrow()->format('Y-m-d'),
            'mentor_program_id' => $this->program->getKey(),
            'web_link'          => null,
        ]);
        $this->event->calendarEventUsers()->attach($this->user->getKey(), ['role' => CalendarEventRoleEnum::HOST]);
    });

    it('updates only web link and redirects', function (): void {
        $payload = ['webLink' => 'https://meet.example.com/abc'];
        $request = Mockery::mock(EditCalendarEventRequest::class);
        $request->shouldReceive('validated')->andReturn($payload);
        $request->shouldReceive('user')->andReturn(Auth::user());

        $response = (new EditCalendarEvent)->handle($request, $this->event);

        expect($response)
            ->toBeInstanceOf(Response::class)
            ->and($response->getTargetUrl())->toBe(route('pages.calendar.index'));

        $updated = CalendarEvent::query()->whereKey($this->event->getKey())->first();
        expect($updated->web_link)->toBe('https://meet.example.com/abc');
    });

    it('returns 403 for non-mentor user', function (): void {
        Auth::logout();
        $anotherMentor = User::factory()->create();
        Auth::login($anotherMentor);
        $this->actingAs($anotherMentor);

        $request = Mockery::mock(EditCalendarEventRequest::class);
        $request->shouldReceive('getEventData')->never();

        $response = $this->withSession(['_token' => 'test-token'])
            ->patch(route('pages.calendar.edit', $this->event),
                [
                    'webLink'  => 'https://deny.me',
                    '_token'   => 'test-token',
                ]
            );

        $response->assertStatus(403);
    });
});

function createAndAuthenticateMentorForCalendarUpdate(): User
{
    $user = User::factory()->create();
    $user->assignRole(Role::findByName(RoleEnum::MENTOR->value));
    Auth::login($user);

    return $user;
}
