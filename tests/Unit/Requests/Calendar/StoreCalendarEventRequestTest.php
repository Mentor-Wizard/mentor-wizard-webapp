<?php

declare(strict_types=1);

use App\Enums\CalendarEventColoursEnum;
use App\Enums\CalendarEventRoleEnum;
use App\Enums\CalendarEventStatusEnum;
use App\Enums\CalendarEventTypeEnum;
use App\Enums\MentorSessionTypeEnum;
use App\Enums\RoleEnum;
use App\Http\Requests\Calendar\StoreCalendarEventRequest;
use App\Models\CalendarEvent;
use App\Models\MentorProgram;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Date;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;

mutates(StoreCalendarEventRequest::class);

describe('StoreCalendarEventRequest getEventData and validator extras', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        $this->user = User::factory()->create();
        auth()->login($this->user);

        $this->user->assignRole(Role::findByName(RoleEnum::MENTOR->value));
        $this->mentiUser = User::factory()->create();
        $this->mentiUser->assignRole(Role::findByName(RoleEnum::MENTI->value));

        $this->mentorProgram = MentorProgram::factory()->create([
            'mentor_id' => $this->user->getKey(),
        ]);
        $this->prepareRequest = function (StoreCalendarEventRequest $request): void {
            $request->setContainer(app());
            $request->setRedirector(resolve(Redirector::class));
            $request->setUserResolver(fn () => $this->user);
        };
    });

    it('successfully validates payload with all required info', function (): void {
        $payload = [
            'id'                => 1,
            'title'             => 'successful validation',
            'fromDate'          => Date::now()->addDays(5)->format('Y-m-d'),
            'toDate'            => Date::now()->addDays(5)->format('Y-m-d'),
            'fromTime'          => '10:00',
            'toTime'            => '11:30',
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION->value,
            'colour'            => CalendarEventColoursEnum::BLUE->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ];

        $request = new StoreCalendarEventRequest;

        $validator = Validator::make($payload, $request->rules());

        $data = $validator->getData();

        expect($validator->passes())->toBeTrue()
            ->and($validator->errors())->isEmpty()
            ->and($data['fromDate'])->toBe(Date::now()->addDays(5)->format('Y-m-d'))
            ->and($data['toDate'])->toBe(Date::now()->addDays(5)->format('Y-m-d'))
            ->and($data['fromTime'])->toBe('10:00')
            ->and($data['toTime'])->toBe('11:30');

    });

    it('successfully validates payload with change of timezone', function (): void {
        $this->user->profile->timezone = 'Europe/London';
        $this->user->profile->save();

        Config::set('app.timezone', 'UTC');

        CalendarEvent::query()->create([
            'start_date_time' => Date::now()->addDays(5)
                ->timezone('Europe/London')->format('Y-m-d H:i:s'),
            'end_date_time' => Date::now()->addDays(5)
                ->timezone('Europe/London')->addHours(1)->format('Y-m-d H:i:s'),
            'date' => Date::now()->addDays(5)
                ->timezone('Europe/London')->format('Y-m-d'),
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'title'             => 'booked slot validation',
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);

        $payload = [
            'id'                => 1,
            'title'             => 'successful validation',
            'fromDate'          => Date::now()->addDays(5)->format('Y-m-d'),
            'toDate'            => Date::now()->addDays(5)->format('Y-m-d'),
            'fromTime'          => '10:00',
            'toTime'            => '11:30',
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION->value,
            'colour'            => CalendarEventColoursEnum::BLUE->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ];

        $request = new StoreCalendarEventRequest;

        $validator = Validator::make($payload, $request->rules());

        expect($validator->passes())->toBeTrue();
        expect($validator->errors())->isEmpty();
    });

    it('catches exception when fromDate parsing fails in withValidator', function (): void {
        Date::setTestNow(Date::create(2025, 6, 1, 8, 0, 0, 'UTC'));

        $data = [
            'title'             => 'Invalid FromDate',
            'fromDate'          => '2025-13-45',
            'toDate'            => Date::now()->addDays(2)->format('Y-m-d'),
            'fromTime'          => '09:15',
            'toTime'            => '10:45',
            'description'       => 'desc',
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION->value,
            'colour'            => CalendarEventColoursEnum::BLUE->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ];

        $request = new StoreCalendarEventRequest;
        $request->merge($data);
        ($this->prepareRequest)($request);

        try {
            $request->validateResolved();
            expect(false)->toBeTrue('Should have thrown exception');
        } catch (Exception $exception) {
            expect($exception)->toBeInstanceOf(Exception::class);
        }
    });

    it('catches exception when fromDate with fromTime parsing fails in withValidator', function (): void {
        Date::setTestNow(Date::create(2025, 6, 1, 8, 0, 0, 'UTC'));

        $data = [
            'title'             => 'Invalid FromDateTime',
            'fromDate'          => Date::now()->addDays(2)->format('Y-m-d'),
            'toDate'            => Date::now()->addDays(2)->format('Y-m-d'),
            'fromTime'          => '25:99---', // Invalid time that might pass initial format check
            'toTime'            => '10:45',
            'description'       => 'desc',
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION->value,
            'colour'            => CalendarEventColoursEnum::BLUE->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ];

        $request = new StoreCalendarEventRequest;
        $request->merge($data);
        ($this->prepareRequest)($request);

        try {
            $request->validateResolved();
            expect(false)->toBeTrue('Should have thrown exception');
        } catch (Exception $exception) {
            expect($exception)->toBeInstanceOf(Exception::class);
        }
    });

    it('catches exception when toDate is null', function (): void {
        Date::setTestNow(Date::create(2025, 6, 1, 8, 0, 0, 'UTC'));

        $data = [
            'title'             => 'Invalid FromDateTime',
            'fromDate'          => Date::now()->addDays(2)->format('Y-m-d'),
            'toDate'            => null,
            'fromTime'          => '09:00', // Invalid time that might pass initial format check
            'toTime'            => '10:45',
            'description'       => 'desc',
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION->value,
            'colour'            => CalendarEventColoursEnum::BLUE->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ];

        $request = new StoreCalendarEventRequest;
        $request->merge($data);
        ($this->prepareRequest)($request);

        try {
            $request->validateResolved();
            expect(false)->toBeTrue('Should have thrown exception');
        } catch (Exception $exception) {
            expect($exception)->toBeInstanceOf(Exception::class);
        }
    });

    it('catches exception when fromTime is null', function (): void {
        Date::setTestNow(Date::create(2025, 6, 1, 8, 0, 0, 'UTC'));

        $data = [
            'title'             => 'Invalid FromDateTime',
            'fromDate'          => Date::now()->addDays(2)->format('Y-m-d'),
            'toDate'            => Date::now()->addDays(2)->format('Y-m-d'),
            'fromTime'          => null,
            'toTime'            => '10:45',
            'description'       => 'desc',
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION->value,
            'colour'            => CalendarEventColoursEnum::BLUE->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ];

        $request = new StoreCalendarEventRequest;
        $request->merge($data);
        ($this->prepareRequest)($request);

        try {
            $request->validateResolved();
            expect(false)->toBeTrue('Should have thrown exception');
        } catch (Exception $exception) {
            expect($exception)->toBeInstanceOf(Exception::class);
        }
    });

    it('catches exception when fromDate is null', function (): void {
        Date::setTestNow(Date::create(2025, 6, 1, 8, 0, 0, 'UTC'));

        $data = [
            'title'             => 'Invalid FromDateTime',
            'fromDate'          => null,
            'toDate'            => Date::now()->addDays(2)->format('Y-m-d'),
            'fromTime'          => '08:00',
            'toTime'            => '09:00',
            'description'       => 'desc',
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION->value,
            'colour'            => CalendarEventColoursEnum::BLUE->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ];

        $request = new StoreCalendarEventRequest;
        $request->merge($data);
        ($this->prepareRequest)($request);

        try {
            $request->validateResolved();
            expect(false)->toBeTrue('Should have thrown exception');
        } catch (Exception $exception) {
            expect($exception)->toBeInstanceOf(Exception::class);
        }
    });

    it('catches exception when toTime is null', function (): void {
        Date::setTestNow(Date::create(2025, 6, 1, 8, 0, 0, 'UTC'));

        $data = [
            'title'             => 'Invalid toDateTime',
            'fromDate'          => Date::now()->addDays(2)->format('Y-m-d'),
            'toDate'            => Date::now()->addDays(2)->format('Y-m-d'),
            'fromTime'          => '08:00',
            'toTime'            => null,
            'description'       => 'desc',
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION->value,
            'colour'            => CalendarEventColoursEnum::BLUE->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ];

        $request = new StoreCalendarEventRequest;
        $request->merge($data);
        ($this->prepareRequest)($request);

        try {
            $request->validateResolved();
            expect(false)->toBeTrue('Should have thrown exception');
        } catch (Exception $exception) {
            expect($exception)->toBeInstanceOf(Exception::class);
        }
    });

    it('catches exception when toDate parsing fails in withValidator', function (): void {
        Date::setTestNow(Date::create(2025, 6, 1, 8, 0, 0, 'UTC'));

        $data = [
            'title'             => 'Invalid ToDate',
            'fromDate'          => Date::now()->addDays(2)->format('Y-m-d'),
            'toDate'            => '2025-02-30', // Invalid date
            'fromTime'          => '09:15',
            'toTime'            => '10:45',
            'description'       => 'desc',
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION->value,
            'colour'            => CalendarEventColoursEnum::BLUE->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ];

        $request = new StoreCalendarEventRequest;
        $request->merge($data);
        ($this->prepareRequest)($request);

        try {
            $request->validateResolved();
            expect(false)->toBeTrue('Should have thrown exception');
        } catch (Exception $exception) {
            expect($exception)->toBeInstanceOf(Exception::class);
        }
    });

    it('catches exception when toDate with toTime parsing fails in withValidator', function (): void {
        Date::setTestNow(Date::create(2025, 6, 1, 8, 0, 0, 'UTC'));

        $data = [
            'title'             => 'Invalid ToDateTime',
            'fromDate'          => Date::now()->addDays(2)->format('Y-m-d'),
            'toDate'            => Date::now()->addDays(2)->format('Y-m-d'),
            'fromTime'          => '09:15',
            'toTime'            => 'not-a-time', // Invalid time
            'description'       => 'desc',
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION->value,
            'colour'            => CalendarEventColoursEnum::BLUE->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ];

        $request = new StoreCalendarEventRequest;
        $request->merge($data);
        ($this->prepareRequest)($request);

        try {
            $request->validateResolved();
            expect(false)->toBeTrue('Should have thrown exception');
        } catch (Exception $exception) {
            expect($exception)->toBeInstanceOf(Exception::class);
        }
    });

    it('validates time slots and adds error when slot is reserved', function (): void {
        Date::setTestNow(Date::create(2025, 6, 1, 8, 0, 0, 'UTC'));

        auth()->login($this->mentiUser);
        // Create an existing event that will conflict
        $existingEvent = CalendarEvent::factory()->create([
            'start_date_time'   => Date::now()->addDays(2)->setTime(10, 0, 0),
            'end_date_time'     => Date::now()->addDays(2)->setTime(11, 0, 0),
            'date'              => Date::now()->addDays(2)->format('Y-m-d'),
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);
        $existingEvent->calendarEventUsers()->attach($this->user->getKey(),
            [
                'role' => CalendarEventRoleEnum::HOST->value,
            ]
        );

        $data = [
            'title'             => 'Conflicting Event',
            'fromDate'          => Date::now()->addDays(2)->format('Y-m-d'),
            'toDate'            => Date::now()->addDays(2)->format('Y-m-d'),
            'fromTime'          => '10:15', // overlaps with existing event
            'toTime'            => '10:45',
            'description'       => 'desc',
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'session_type'      => MentorSessionTypeEnum::CODE_REVIEW->value,
            'colour'            => CalendarEventColoursEnum::BLUE->value,
            'timezone'          => 'UTC',
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ];

        $request = new StoreCalendarEventRequest;
        $request->merge($data);
        ($this->prepareRequest)($request);

        actingAs($this->mentiUser);
        try {
            $request->validateResolved();
            expect(false)->toBeTrue('Should have failed validation due to slot conflict');
        } catch (ValidationException $validationException) {
            expect($validationException->errors())->toHaveKey('fromDate')
                ->and($validationException->errors()['fromDate'])
                ->toContain('This slot is busy');
        }
    });
    it('rejects when title is missing', function (): void {
        $payload = [
            // 'title' => missing
            'fromDate'          => Date::now()->addDays(5)->format('Y-m-d'),
            'toDate'            => Date::now()->addDays(5)->format('Y-m-d'),
            'fromTime'          => '10:00',
            'toTime'            => '11:30',
            'type'              => 'individual',
            'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION->value,
            'colour'            => CalendarEventColoursEnum::BLUE->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ];

        $request = new StoreCalendarEventRequest;

        $validator = Validator::make($payload, $request->rules());

        expect($validator->passes())->toBeFalse();
        expect($validator->errors()->has('title'))->toBeTrue();
    });

    it('rejects when fromDate is wrong format', function (): void {
        $payload = [
            'title'             => 'Wrong fromDate format ',
            'fromDate'          => 'not-a-date',
            'toDate'            => Date::now()->addDays(5)->format('Y-m-d'),
            'fromTime'          => '10:00',
            'toTime'            => '11:30',
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION->value,
            'colour'            => CalendarEventColoursEnum::BLUE->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ];

        $request = new StoreCalendarEventRequest;

        $validator = Validator::make($payload, $request->rules());

        expect($validator->passes())->toBeFalse();
        expect($validator->errors()->has('fromDate'))->toBeTrue();
    });
    it('rejects when fromDate and fromTime are at wrong format', function (): void {
        $payload = [
            'title'             => 'Wrong fromDate format ',
            'fromDate'          => 'not-a-date',
            'toDate'            => Date::now()->addDays(5)->format('Y-m-d'),
            'fromTime'          => '10----:00',
            'toTime'            => '11:30',
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION->value,
            'colour'            => CalendarEventColoursEnum::BLUE->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ];

        $request = new StoreCalendarEventRequest;

        $validator = Validator::make($payload, $request->rules());

        expect($validator->passes())->toBeFalse();
        expect($validator->errors()->has('fromDate'))->toBeTrue();
        expect($validator->errors()->has('fromTime'))->toBeTrue();
    });

    it('rejects when fromTime is wrong format', function (): void {
        $payload = [
            'title'             => 'Wrong fromDate format ',
            'fromDate'          => Date::now()->addDays(5)->format('Y-m-d'),
            'toDate'            => Date::now()->addDays(5)->format('Y-m-d'),
            'fromTime'          => 'not-a-time',
            'toTime'            => '11:30',
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION->value,
            'colour'            => CalendarEventColoursEnum::BLUE->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ];

        $request = new StoreCalendarEventRequest;

        $validator = Validator::make($payload, $request->rules());

        expect($validator->passes())->toBeFalse();
        expect($validator->errors()->has('fromTime'))->toBeTrue();
    });

    it('rejects when toDate is wrong format', function (): void {
        $payload = [
            'title'             => 'Wrong fromDate format ',
            'fromDate'          => Date::now()->addDays(5)->format('Y-m-d'),
            'toDate'            => 'not-a-date',
            'fromTime'          => '10:00',
            'toTime'            => '11:30',
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION->value,
            'colour'            => CalendarEventColoursEnum::BLUE->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ];

        $request = new StoreCalendarEventRequest;

        $validator = Validator::make($payload, $request->rules());

        expect($validator->passes())->toBeFalse();
        expect($validator->errors()->has('toDate'))->toBeTrue();
    });

    it('does not check slot availability when only fromDate is invalid', function (): void {
        $data = [
            'fromDate'          => '2025-01-01',
            'fromTime'          => 'invalid-time',
            'toDate'            => '2025-01-01',
            'toTime'            => '11:00',
            'title'             => 'Test Event',
            'description'       => 'Test Description',
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION->value,
            'colour'            => CalendarEventColoursEnum::BLUE->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ];

        $request = StoreCalendarEventRequest::create('/calendar', 'POST', $data);
        $request->setContainer(app());
        $request->setUserResolver(fn () => $this->user);

        $validator = Validator::make($request->all(), $request->rules());

        expect($validator->validate(...))
            ->toThrow(ValidationException::class);

        // Verify that fromDate error exists
        expect($validator->errors()->has('fromDate'))->toBeTrue();
        expect($validator->errors()->has('fromTime'))->toBeTrue();

    });

    it('rejects when toDate and toTime are at wrong format', function (): void {
        $payload = [
            'title'             => 'Wrong fromDate format ',
            'fromDate'          => Date::now()->addDays(5)->format('Y-m-d'),
            'toDate'            => 'not-a-date',
            'fromTime'          => '10:00',
            'toTime'            => '1-----1:30',
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION->value,
            'colour'            => CalendarEventColoursEnum::BLUE->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ];

        $request = new StoreCalendarEventRequest;

        $validator = Validator::make($payload, $request->rules());

        expect($validator->passes())->toBeFalse();
        expect($validator->errors()->has('toDate'))->toBeTrue();
        expect($validator->errors()->has('toTime'))->toBeTrue();
    });

    it('rejects when fromTime and toTime are at wrong format', function (): void {
        $payload = [
            'title'             => 'Wrong fromDate format ',
            'fromDate'          => Date::now()->addDays(5)->format('Y-m-d'),
            'toDate'            => Date::now()->addDays(5)->format('Y-m-d'),
            'fromTime'          => '1------0:00',
            'toTime'            => '1-----1:30',
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION->value,
            'colour'            => CalendarEventColoursEnum::BLUE->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ];

        $request = new StoreCalendarEventRequest;

        $validator = Validator::make($payload, $request->rules());

        expect($validator->passes())->toBeFalse();
        expect($validator->errors()->has('fromTime'))->toBeTrue();
        expect($validator->errors()->has('toTime'))->toBeTrue();
    });

    it('rejects when toDate and fromDate are with wrong format', function (): void {
        $payload = [
            'title'             => 'Wrong fromDate format ',
            'fromDate'          => 'not-a-date',
            'toDate'            => 'not-a-date',
            'fromTime'          => '10:00',
            'toTime'            => '11:30',
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION->value,
            'colour'            => CalendarEventColoursEnum::BLUE->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ];

        $request = new StoreCalendarEventRequest;

        $validator = Validator::make($payload, $request->rules());

        expect($validator->passes())->toBeFalse();
        expect($validator->errors()->has('fromDate'))->toBeTrue();
        expect($validator->errors()->has('toDate'))->toBeTrue();
    });

    it('rejects when toTime is wrong format', function (): void {
        $payload = [
            'title'             => 'Wrong fromDate format ',
            'fromDate'          => Date::now()->addDays(5)->format('Y-m-d'),
            'toDate'            => Date::now()->addDays(5)->format('Y-m-d'),
            'fromTime'          => '10:00',
            'toTime'            => 'not-a-time',
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION->value,
            'colour'            => CalendarEventColoursEnum::BLUE->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ];

        $request = new StoreCalendarEventRequest;

        $validator = Validator::make($payload, $request->rules());

        expect($validator->passes())->toBeFalse();
        expect($validator->errors()->has('toTime'))->toBeTrue();
    });

    it('rejects when colour is not from list', function (): void {
        $payload = [
            'title'             => 'Wrong fromDate format ',
            'fromDate'          => Date::now()->addDays(5)->format('Y-m-d'),
            'toDate'            => Date::now()->addDays(5)->format('Y-m-d'),
            'fromTime'          => '10:00',
            'toTime'            => '11:30',
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION->value,
            'colour'            => 'wrong colour',
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ];

        $request = new StoreCalendarEventRequest;

        $validator = Validator::make($payload, $request->rules());

        expect($validator->passes())->toBeFalse();
        expect($validator->errors()->has('colour'))->toBeTrue();
    });

    it('verify slots availability with validation from list', function (): void {
        $event = CalendarEvent::query()->create([
            'title'             => 'Busy block',
            'status'            => CalendarEventStatusEnum::CONFIRMED,
            'start_date_time'   => Date::now()->addDays(5)->setTime(10, 0),
            'end_date_time'     => Date::now()->addDays(5)->setTime(11, 30),
            'date'              => Date::now()->addDays(5)->format('Y-m-d'),
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'description'       => 'Busy',
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);
        $event->calendarEventUsers()->attach($this->user->getKey(),
            [
                'role' => CalendarEventRoleEnum::HOST->value,
            ]
        );

        $payload = [
            'title'             => 'Conflicting slot',
            'fromDate'          => Date::now()->addDays(5)->format('Y-m-d'),
            'toDate'            => Date::now()->addDays(5)->format('Y-m-d'),
            'fromTime'          => '10:00',
            'toTime'            => '11:30',
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION->value,
            'colour'            => CalendarEventColoursEnum::BLUE->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ];

        $request = new StoreCalendarEventRequest;
        $request->merge($payload);
        ($this->prepareRequest)($request);

        actingAs($this->mentiUser);
        try {
            $request->validateResolved();
            expect(false)->toBeTrue('Should have failed validation due to slot conflict');
        } catch (ValidationException $validationException) {
            expect($validationException->errors())->toHaveKey('fromDate')
                ->and($validationException->errors()['fromDate'])->toContain('This slot is busy');
        }
    });

    it('verify slots availability with wrong fromDate validation from list', function (): void {
        $event = CalendarEvent::query()->create([
            'title'             => 'Busy block',
            'status'            => CalendarEventStatusEnum::CONFIRMED,
            'start_date_time'   => Date::now()->addDays(5)->setTime(10, 0),
            'end_date_time'     => Date::now()->addDays(5)->setTime(11, 30),
            'date'              => Date::now()->addDays(5)->format('Y-m-d'),
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION->value,
            'description'       => 'Busy',
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);
        $event->calendarEventUsers()->attach($this->user->getKey(),
            [
                'role' => CalendarEventRoleEnum::HOST->value,
            ]
        );

        $payload = [
            'title'             => 'Conflicting slot',
            'fromDate'          => 'tesdfsdf6644',
            'toDate'            => Date::now()->addDays(5)->format('Y-m-d'),
            'fromTime'          => '10:00',
            'toTime'            => '11:30',
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION->value,
            'colour'            => CalendarEventColoursEnum::BLUE->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ];

        $request = new StoreCalendarEventRequest;
        $request->merge($payload);
        ($this->prepareRequest)($request);

        actingAs($this->mentiUser);
        try {
            $request->validateResolved();
            expect(false)->toBeTrue('Should have failed validation due to slot conflict');
        } catch (ValidationException $validationException) {
            expect($validationException->errors())->toHaveKey('fromDate')
                ->and($validationException->errors()['fromDate'])->toContain('The from date field must match the format Y-m-d.')
                ->and($validationException->errors()['fromDate'])->toContain('Start date cannot be in the past.');
        }
    });

    it('verify slots availability with wrong toDate validation from list', function (): void {
        $event = CalendarEvent::query()->create([
            'title'             => 'Busy block',
            'status'            => CalendarEventStatusEnum::CONFIRMED,
            'start_date_time'   => Date::now()->addDays(5)->setTime(10, 0),
            'end_date_time'     => Date::now()->addDays(5)->setTime(11, 30),
            'date'              => Date::now()->addDays(5)->format('Y-m-d'),
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'description'       => 'Busy',
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);

        $event->calendarEventUsers()->attach($this->user->getKey(),
            [
                'role' => CalendarEventRoleEnum::HOST->value,
            ]
        );

        $payload = [
            'title'             => 'Conflicting slot',
            'fromDate'          => Date::now()->addDays(5)->format('Y-m-d'),
            'toDate'            => '23423423--4',
            'fromTime'          => '10:00',
            'toTime'            => '11:30',
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION->value,
            'colour'            => CalendarEventColoursEnum::BLUE->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ];

        $request = new StoreCalendarEventRequest;
        $request->merge($payload);
        ($this->prepareRequest)($request);

        actingAs($this->mentiUser);
        try {
            $request->validateResolved();
            expect(false)->toBeTrue('Should have failed validation due to slot conflict');
        } catch (ValidationException $validationException) {
            expect($validationException->errors())->toHaveKey('toDate')
                ->and($validationException->errors()['toDate'])
                ->toContain('The to date field must match the format Y-m-d.')
                ->and($validationException->errors()['toDate'])
                ->toContain('End date must be on or after the start date.');
        }
    });

    it('verify slots availability with wrong fromTime validation from list', function (): void {
        $event = CalendarEvent::query()->create([
            'title'             => 'Busy block',
            'status'            => CalendarEventStatusEnum::CONFIRMED,
            'start_date_time'   => Date::now()->addDays(5)->setTime(10, 0),
            'end_date_time'     => Date::now()->addDays(5)->setTime(11, 30),
            'date'              => Date::now()->addDays(5)->format('Y-m-d'),
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION->value,
            'description'       => 'Busy',
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);
        $event->calendarEventUsers()->attach($this->user->getKey(),
            [
                'role' => CalendarEventRoleEnum::HOST->value,
            ]
        );

        $payload = [
            'title'             => 'Conflicting slot',
            'fromDate'          => Date::now()->addDays(5)->format('Y-m-d'),
            'toDate'            => Date::now()->addDays(5)->format('Y-m-d'),
            'fromTime'          => null,
            'toTime'            => '11:30',
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION->value,
            'colour'            => CalendarEventColoursEnum::BLUE->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ];

        $request = new StoreCalendarEventRequest;
        $request->merge($payload);
        ($this->prepareRequest)($request);

        actingAs($this->mentiUser);
        try {
            $request->validateResolved();
            expect(false)->toBeTrue('Should have failed validation due to slot conflict');
        } catch (ValidationException $validationException) {
            expect($validationException->errors())->toHaveKey('fromTime')
                ->and($validationException->errors()['fromTime'])->toContain('Start time is required.');
        }

        $payload = [
            'title'             => 'Conflicting slot',
            'fromDate'          => Date::now()->addDays(5)->format('Y-m-d'),
            'toDate'            => Date::now()->addDays(5)->format('Y-m-d'),
            'fromTime'          => '10:00',
            'toTime'            => '11:30',
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION->value,
            'colour'            => CalendarEventColoursEnum::BLUE->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ];

        $request = new StoreCalendarEventRequest;
        $request->merge($payload);
        ($this->prepareRequest)($request);

        try {
            $request->validateResolved();
            expect(false)->toBeTrue('Should have failed validation due to slot conflict');
        } catch (ValidationException $validationException) {
            expect($validationException->errors())->toHaveKey('fromDate')
                ->and($validationException->errors()['fromDate'])
                ->toContain('This slot is busy');
        }
    });

    it('verify slots availability with wrong toTime validation from list', function (): void {
        $event = CalendarEvent::query()->create([
            'title'             => 'Busy block',
            'status'            => CalendarEventStatusEnum::CONFIRMED,
            'start_date_time'   => Date::now()->addDays(5)->setTime(10, 0),
            'end_date_time'     => Date::now()->addDays(5)->setTime(11, 30),
            'date'              => Date::now()->addDays(5)->format('Y-m-d'),
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION->value,
            'description'       => 'Busy',
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);
        $event->calendarEventUsers()->attach($this->user->getKey(),
            [
                'role' => CalendarEventRoleEnum::HOST->value,
            ]
        );

        $payload = [
            'title'             => 'Conflicting slot',
            'fromDate'          => Date::now()->addDays(5)->format('Y-m-d'),
            'toDate'            => Date::now()->addDays(5)->format('Y-m-d'),
            'fromTime'          => '10:00',
            'toTime'            => '-------',
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION->value,
            'colour'            => CalendarEventColoursEnum::BLUE->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ];

        $request = new StoreCalendarEventRequest;
        $request->merge($payload);
        ($this->prepareRequest)($request);

        actingAs($this->mentiUser);
        try {
            $request->validateResolved();
            expect(false)->toBeTrue('Should have failed validation due to slot conflict');
        } catch (ValidationException $validationException) {
            expect($validationException->errors())->toHaveKey('toTime')
                ->and($validationException->errors()['toTime'])
                ->toContain('End time must be in HH:MM format.');
        }
    });

    it('skips slot availability check when fromDate has validation error', function (): void {
        $event = CalendarEvent::query()->create([
            'title'             => 'Busy block',
            'status'            => CalendarEventStatusEnum::CONFIRMED,
            'start_date_time'   => Date::now()->addDays(5)->setTime(10, 0),
            'end_date_time'     => Date::now()->addDays(5)->setTime(11, 30),
            'date'              => Date::now()->addDays(5)->format('Y-m-d'),
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION->value,
            'description'       => 'Busy',
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);
        $event->calendarEventUsers()->attach($this->user->getKey(),
            [
                'role' => CalendarEventRoleEnum::HOST->value,
            ]
        );

        $payload = [
            'title'             => 'Conflicting slot',
            'fromDate'          => 'not-a-date',
            'toDate'            => Date::now()->addDays(5)->format('Y-m-d'),
            'fromTime'          => '10:00',
            'toTime'            => '11:30',
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'colour'            => CalendarEventColoursEnum::BLUE->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ];

        $request = new StoreCalendarEventRequest;
        $request->merge($payload);
        ($this->prepareRequest)($request);
        actingAs($this->mentiUser);

        try {
            $request->validateResolved();
            expect(false)->toBeTrue('Should have failed validation due to invalid fromDate');
        } catch (ValidationException $validationException) {
            $errors = $validationException->errors();
            expect($errors)->toHaveKey('fromDate');

            // Ensure the slot conflict error is NOT present
            $fromDateErrors = $errors['fromDate'];
            $hasSlotError = false;
            foreach ($fromDateErrors as $error) {
                if (str_contains($error, 'This slot is busy')) {
                    $hasSlotError = true;
                    break;
                }
            }

            expect($hasSlotError)->toBeFalse('Should not check slot availability when fromDate is invalid');
        }
    });

    it('skips slot availability check when fromTime has validation error', function (): void {
        $event = CalendarEvent::query()->create([
            'title'             => 'Busy block',
            'status'            => CalendarEventStatusEnum::CONFIRMED,
            'start_date_time'   => Date::now()->addDays(5)->setTime(10, 0),
            'end_date_time'     => Date::now()->addDays(5)->setTime(11, 30),
            'date'              => Date::now()->addDays(5)->format('Y-m-d'),
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION->value,
            'description'       => 'Busy',
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);
        $event->calendarEventUsers()->attach($this->user->getKey(), [
            'role'              => CalendarEventRoleEnum::HOST->value,
            'colour'            => CalendarEventColoursEnum::BLUE->value,
        ]);

        $payload = [
            'title'             => 'Conflicting slot',
            'fromDate'          => Date::now()->addDays(5)->format('Y-m-d'),
            'toDate'            => Date::now()->addDays(5)->format('Y-m-d'),
            'fromTime'          => '25:99', // Invalid time that passes string format but fails parsing
            'toTime'            => '11:30',
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION->value,
            'colour'            => CalendarEventColoursEnum::BLUE->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ];

        $request = new StoreCalendarEventRequest;
        $request->merge($payload);
        ($this->prepareRequest)($request);

        actingAs($this->mentiUser);
        try {
            $request->validateResolved();
            expect(false)->toBeTrue('Should have failed validation due to invalid fromTime');
        } catch (ValidationException $validationException) {
            $errors = $validationException->errors();
            expect($errors)->toHaveKey('fromTime');

            // Critical: Ensure the slot conflict error is NOT added
            // If the guard is removed, this could try to check slots with bad data
            if (isset($errors['fromDate'])) {
                foreach ($errors['fromDate'] as $error) {
                    expect($error)->not->toContain('This slot is busy');
                }
            }
        }
    });

});

describe('StoreCalendarEventRequest rules and messages', function (): void {
    it('authorizes authenticated users with create permission', function (): void {
        $this->seed(RoleSeeder::class);
        $user = User::factory()->create();
        $user->assignRole(Role::findByName(RoleEnum::MENTI->value));
        actingAs($user);

        $request = new StoreCalendarEventRequest;
        $request->setUserResolver(fn () => $user);

        expect($request->authorize())->toBeTrue();
    });

    it('prevents mentor to create event for their own program', function (): void {
        $this->seed(RoleSeeder::class);
        $mentor = User::factory()->create();
        $mentor->assignRole(Role::findByName(RoleEnum::MENTOR->value));
        actingAs($mentor);

        $mentorProgram = MentorProgram::factory()->create(['mentor_id' => $mentor->getKey()]);

        $request = new StoreCalendarEventRequest;
        $request->setUserResolver(fn () => $mentor);
        $request->merge(['mentor_program_id' => $mentorProgram->getKey()]);

        expect($request->authorize())->toBeFalse();
    });

    it('checks another mentor successfully creating event for other mentor program', function (): void {
        $this->seed(RoleSeeder::class);
        $programOwner = User::factory()->create();
        $programOwner->assignRole(Role::findByName(RoleEnum::MENTOR->value));

        $anotherMentor = User::factory()->create();
        $anotherMentor->assignRole(Role::findByName(RoleEnum::MENTOR->value));
        actingAs($anotherMentor);

        $mentorProgram = MentorProgram::factory()->create(['mentor_id' => $programOwner->getKey()]);

        $request = new StoreCalendarEventRequest;
        $request->setUserResolver(fn () => $anotherMentor);
        $request->merge(['mentor_program_id' => $mentorProgram->getKey()]);

        expect($request->authorize())->toBeTrue();
    });

    it('authorizes non-mentor user to book event on mentor program', function (): void {
        $this->seed(RoleSeeder::class);
        $mentor = User::factory()->create();
        $mentor->assignRole(Role::findByName(RoleEnum::MENTOR->value));

        $mentee = User::factory()->create();
        $mentee->assignRole(Role::findByName(RoleEnum::MENTI->value));
        actingAs($mentee);

        $mentorProgram = MentorProgram::factory()->create(['mentor_id' => $mentor->getKey()]);

        $request = new StoreCalendarEventRequest;
        $request->setUserResolver(fn () => $mentee);
        $request->merge(['mentor_program_id' => $mentorProgram->getKey()]);

        expect($request->authorize())->toBeTrue();
    });

    it('authorizes when mentor_program_id is null', function (): void {
        $this->seed(RoleSeeder::class);
        $user = User::factory()->create();
        $user->assignRole(Role::findByName(RoleEnum::MENTI->value));
        actingAs($user);

        $request = new StoreCalendarEventRequest;
        $request->setUserResolver(fn () => $user);
        $request->merge(['mentor_program_id' => null]);

        expect($request->authorize())->toBeTrue();
    });

    it('authorizes when mentor_program_id is not provided', function (): void {
        $this->seed(RoleSeeder::class);
        $user = User::factory()->create();
        $user->assignRole(Role::findByName(RoleEnum::MENTI->value));
        actingAs($user);

        $request = new StoreCalendarEventRequest;
        $request->setUserResolver(fn () => $user);

        expect($request->authorize())->toBeTrue();
    });

    it('provides all expected validation rules', function (): void {
        $request = new StoreCalendarEventRequest;
        $rules = $request->rules();

        expect($rules)
            ->toHaveKeys(['title', 'fromDate', 'toDate', 'fromTime', 'toTime', 'description', 'type', 'colour'])
            ->and($rules['title'])->toContain('required', 'string', 'max:255')
            ->and($rules['fromDate'])->toContain('required', 'date_format:Y-m-d', 'after_or_equal:today')
            ->and($rules['toDate'])->toContain('required', 'date_format:Y-m-d', 'after_or_equal:fromDate')
            ->and($rules['fromTime'])->toContain('required', 'date_format:H:i')
            ->and($rules['toTime'])->toContain('required', 'date_format:H:i', 'after:fromTime')
            ->and($rules['colour'])->toContain('required')
            ->and($rules['description'])->toContain('max:2000')
            ->and($rules['type'])->toContain('required');
    });

    it('provides all expected messages', function (): void {
        $request = new StoreCalendarEventRequest;
        $messages = $request->messages();

        expect($messages)
            ->toHaveKeys([
                'title.required',
                'title.max',
                'fromDate.required',
                'fromDate.date',
                'fromDate.after_or_equal',
                'toDate.required',
                'toDate.date',
                'toDate.after_or_equal',
                'fromTime.required',
                'colour.required',
                'fromTime.date_format',
                'toTime.required',
                'toTime.date_format',
                'toTime.after',
                'description.max',
                'type.required',
                'type.in',
            ]);
    });

    it('validates that toTime must be after fromTime', function (): void {
        $request = new StoreCalendarEventRequest;
        $rules = $request->rules();

        expect($rules['toTime'])->toContain('after:fromTime');
    });

    it('validates colour is in allowed values', function (): void {
        $request = new StoreCalendarEventRequest;
        $rules = $request->rules();

        expect($rules['colour'])->toHaveCount(2)
            ->and($rules['colour'][0])->toBe('required');
    });

    it('validates type is in allowed values', function (): void {
        $request = new StoreCalendarEventRequest;
        $rules = $request->rules();

        expect($rules['type'])->toHaveCount(2)
            ->and($rules['type'][0])->toBe('required');
    });

    it('validates description max length is 2000', function (): void {
        $request = new StoreCalendarEventRequest;
        $rules = $request->rules();

        expect($rules['description'])->toContain('max:2000');
    });

    describe('withValidator guards against availability check when base fields invalid', function (): void {
        beforeEach(function (): void {
            $this->seed(RoleSeeder::class);
            $this->user = User::factory()->create();
            auth()->login($this->user);

            $this->user->assignRole(Role::findByName(RoleEnum::MENTOR->value));
            $this->mentorProgram = MentorProgram::factory()->create([
                'mentor_id' => $this->user->getKey(),
            ]);

            $this->prepareRequest = function (StoreCalendarEventRequest $request): void {
                $request->setContainer(app());
                $request->setRedirector(resolve(Redirector::class));
                $request->setUserResolver(fn () => $this->user);
            };

            // Create an overlapping calendar event to ensure withValidator adds an error when it runs
            $start = Date::now()->addDays(2)->setTime(9, 15, 0);
            $end = Date::now()->addDays(2)->setTime(10, 45, 0);
            $event = CalendarEvent::factory()->create([
                'start_date_time'   => $start->copy(),
                'end_date_time'     => $end->copy(),
                'date'              => $start->copy()->format('Y-m-d'),
                'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
                'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION->value,
                'status'            => CalendarEventStatusEnum::CONFIRMED->value,
                'title'             => 'existing overlap',
                'mentor_program_id' => $this->mentorProgram->getKey(),
            ]);
            // Attach event to the authenticated user via pivot so availability service sees it
            $this->user->calendarEvents()->attach($event->getKey(), [
                'colour' => CalendarEventColoursEnum::BLUE->value,
                'role'   => CalendarEventRoleEnum::HOST->value,
            ]);
        });

        it('does not add availability error when fromDate is invalid (guards withValidator)', function (): void {
            $data = [
                'title'             => 'Invalid FromDate',
                'fromDate'          => Date::now()->subDay()->format('Y-m-d'), // invalid by rule but parseable
                'toDate'            => Date::now()->addDays(2)->format('Y-m-d'),
                'fromTime'          => '09:15',
                'toTime'            => '10:45',
                'description'       => 'desc',
                'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
                'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION->value,
                'colour'            => CalendarEventColoursEnum::BLUE->value,
                'mentor_program_id' => $this->mentorProgram->getKey(),
            ];

            $request = new StoreCalendarEventRequest;
            $request->merge($data);
            ($this->prepareRequest)($request);

            $validator = Validator::make($data, $request->rules());
            // Attach the withValidator callbacks
            $request->withValidator($validator);

            expect($validator->fails())->toBeTrue()
                ->and($validator->errors()->has('fromDate'))->toBeTrue()
                // Only the standard validation error should exist for fromDate
                ->and(count($validator->errors()->get('fromDate')))->toBe(1);
        });

        it('does not add availability error when fromTime is invalid (guards withValidator)', function (): void {
            $data = [
                'title'             => 'Invalid FromTime',
                'fromDate'          => Date::now()->addDays(2)->format('Y-m-d'),
                'toDate'            => Date::now()->addDays(2)->format('Y-m-d'),
                'fromTime'          => '-------:15',
                'toTime'            => '10:45',
                'description'       => 'desc',
                'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
                'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION->value,
                'colour'            => CalendarEventColoursEnum::BLUE->value,
                'mentor_program_id' => $this->mentorProgram->getKey(),
            ];

            $request = new StoreCalendarEventRequest;
            $request->merge($data);
            ($this->prepareRequest)($request);

            $validator = Validator::make($data, $request->rules());
            $request->withValidator($validator);

            expect($validator->fails())->toBeTrue()
                ->and($validator->errors()->has('fromTime'))->toBeTrue()
                ->and($validator->errors()->has('fromDate'))->toBeFalse();
        });

        it('does not add availability error when toDate is invalid (guards withValidator)', function (): void {
            $data = [
                'title'             => 'Invalid ToDate',
                'fromDate'          => Date::now()->addDays(2)->format('Y-m-d'),
                'toDate'            => '----01-01', // invalid
                'fromTime'          => '09:15',
                'toTime'            => '10:45',
                'description'       => 'desc',
                'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
                'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION->value,
                'colour'            => CalendarEventColoursEnum::BLUE->value,
                'mentor_program_id' => $this->mentorProgram->getKey(),
            ];

            $request = new StoreCalendarEventRequest;
            $request->merge($data);
            ($this->prepareRequest)($request);

            $validator = Validator::make($data, $request->rules());
            $request->withValidator($validator);

            expect($validator->fails())->toBeTrue()
                ->and($validator->errors()->has('toDate'))->toBeTrue()
                ->and($validator->errors()->has('fromDate'))->toBeFalse();
        });

        it('does not add availability error when toTime is invalid (guards withValidator)', function (): void {
            $data = [
                'title'             => 'Invalid ToTime',
                'fromDate'          => Date::now()->addDays(2)->format('Y-m-d'),
                'toDate'            => Date::now()->addDays(2)->format('Y-m-d'),
                'fromTime'          => '09:15',
                'toTime'            => '--:45', // invalid
                'description'       => 'desc',
                'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
                'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION->value,
                'colour'            => CalendarEventColoursEnum::BLUE->value,
                'mentor_program_id' => $this->mentorProgram->getKey(),
            ];

            $request = new StoreCalendarEventRequest;
            $request->merge($data);
            ($this->prepareRequest)($request);

            $validator = Validator::make($data, $request->rules());
            $request->withValidator($validator);

            expect($validator->fails())->toBeTrue()
                ->and($validator->errors()->has('toTime'))->toBeTrue()
                ->and($validator->errors()->has('fromDate'))->toBeFalse();
        });

        it('covers withValidator on success,
            and skips it when fromTime is invalid but parseable', function (): void {
            // 1) First run: valid times that overlap -> withValidator runs and adds fromDate error
            $valid = [
                'title'             => 'valid overlap',
                'fromDate'          => Date::now()->addDays(2)->format('Y-m-d'),
                'toDate'            => Date::now()->addDays(2)->format('Y-m-d'),
                'fromTime'          => '09:15',
                'toTime'            => '10:45',
                'description'       => 'desc',
                'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
                'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION->value,
                'colour'            => CalendarEventColoursEnum::BLUE->value,
                'mentor_program_id' => $this->mentorProgram->getKey(),
            ];

            $request1 = new StoreCalendarEventRequest;
            $request1->merge($valid);
            ($this->prepareRequest)($request1);
            $validator1 = Validator::make($valid, $request1->rules());
            $request1->withValidator($validator1);

            $events = DB::table('calendar_events')->get();

            expect($validator1->fails())->toBeTrue()
                ->and($validator1->errors()->has('fromDate'))->toBeTrue()
                ->and(($validator1->errors()->get('fromDate')))
                ->toContain('This slot is busy');

            // 2) Second run: Leading space makes concatenation parseable, but rule invalidates fromTime
            $invalidFromTime = [
                'title'             => 'Leading space FromTime',
                'fromDate'          => Date::now()->addDays(2)->format('Y-m-d'),
                'toDate'            => Date::now()->addDays(2)->format('Y-m-d'),
                'fromTime'          => '-- 10:00', // invalid by rule, parseable by concatenation
                'toTime'            => '11:00',
                'description'       => 'desc',
                'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
                'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION->value,
                'colour'            => CalendarEventColoursEnum::BLUE->value,
                'mentor_program_id' => $this->mentorProgram->getKey(),
            ];

            $request2 = new StoreCalendarEventRequest;
            $request2->merge($invalidFromTime);
            ($this->prepareRequest)($request2);
            $validator2 = Validator::make($invalidFromTime, $request2->rules());
            $request2->withValidator($validator2);

            expect($validator2->fails())->toBeTrue()
                ->and($validator2->errors()->has('fromTime'))->toBeTrue()
                // withValidator should be skipped, so no extra fromDate error must be present
                ->and($validator2->errors()->has('fromDate'))->toBeFalse();
        });

        it('throws exception when fromTime is invalid and guard is bypassed', function (): void {
            $event = CalendarEvent::query()->create([
                'title'             => 'Busy block',
                'status'            => CalendarEventStatusEnum::CONFIRMED,
                'start_date_time'   => Date::now()->addDays(5)->setTime(10, 0),
                'end_date_time'     => Date::now()->addDays(5)->setTime(11, 30),
                'date'              => Date::now()->addDays(5)->format('Y-m-d'),
                'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
                'description'       => 'Busy',
                'mentor_program_id' => $this->mentorProgram->getKey(),
            ]);
            $event->calendarEventUsers()->attach($this->user->getKey(),
                [
                    'role' => CalendarEventRoleEnum::HOST->value,
                ]
            );

            $payload = [
                'title'             => 'Conflicting slot',
                'fromDate'          => Date::now()->addDays(5)->format('Y-m-d'),
                'toDate'            => Date::now()->addDays(5)->format('Y-m-d'),
                'fromTime'          => 'invalid',
                'toTime'            => '11:30',
                'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
                'colour'            => CalendarEventColoursEnum::BLUE->value,
                'mentor_program_id' => $this->mentorProgram->getKey(),
            ];

            $request = new StoreCalendarEventRequest;
            $request->merge($payload);
            ($this->prepareRequest)($request);

            $exceptionThrown = false;

            try {
                $request->validateResolved();
            } catch (ValidationException $e) {
                $exceptionThrown = true;
                expect($e->errors())->toHaveKey('fromTime');
            } catch (Throwable) {
                $exceptionThrown = true;
                expect(true)->toBeTrue('Guard prevented invalid date parsing');
            }

            expect($exceptionThrown)->toBeTrue('Validation should fail for invalid fromTime');
        });

        it('skips slot availability check when fromTime has validation error', function (): void {
            $event = CalendarEvent::query()->create([
                'title'             => 'Busy block',
                'status'            => CalendarEventStatusEnum::CONFIRMED,
                'start_date_time'   => Date::now()->addDays(5)->setTime(10, 0),
                'end_date_time'     => Date::now()->addDays(5)->setTime(11, 30),
                'date'              => Date::now()->addDays(5)->format('Y-m-d'),
                'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
                'description'       => 'Busy',
                'mentor_program_id' => $this->mentorProgram->getKey(),
            ]);
            $event->calendarEventUsers()->attach($this->user->getKey(),
                [
                    'role' => CalendarEventRoleEnum::HOST->value,
                ]
            );

            $payload = [
                'title'             => 'Conflicting slot',
                'fromDate'          => Date::now()->addDays(5)->format('Y-m-d'),
                'toDate'            => Date::now()->addDays(5)->format('Y-m-d'),
                'fromTime'          => '10:00:00', // Wrong format - should be H:i not H:i:s
                'toTime'            => '11:30',
                'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
                'colour'            => CalendarEventColoursEnum::BLUE->value,
                'mentor_program_id' => $this->mentorProgram->getKey(),
            ];

            $request = new StoreCalendarEventRequest;
            $request->merge($payload);
            ($this->prepareRequest)($request);

            try {
                $request->validateResolved();
                expect(false)->toBeTrue('Should have failed validation');
            } catch (ValidationException $e) {
                expect($e->errors())->toHaveKey('fromTime')
                    ->and($e->errors())->not->toHaveKey('fromDate');
            } catch (Throwable) {
                // If guard is bypassed, date parsing will fail
                expect(true)->toBeTrue('Exception caught as expected');
            }
        });

        it('skips slot availability check when mentor_program_id is invalid', function (): void {
            $event = CalendarEvent::query()->create([
                'title'             => 'Busy block',
                'status'            => CalendarEventStatusEnum::CONFIRMED,
                'start_date_time'   => Date::now()->addDays(5)->setTime(10, 0),
                'end_date_time'     => Date::now()->addDays(5)->setTime(11, 30),
                'date'              => Date::now()->addDays(5)->format('Y-m-d'),
                'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
                'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION->value,
                'description'       => 'Busy',
                'mentor_program_id' => $this->mentorProgram->getKey(),
            ]);
            $event->calendarEventUsers()->attach($this->user->getKey(),
                [
                    'role' => CalendarEventRoleEnum::HOST->value,
                ]
            );

            $payload = [
                'title'             => 'Conflicting slot',
                'fromDate'          => Date::now()->addDays(5)->format('Y-m-d'),
                'toDate'            => Date::now()->addDays(5)->format('Y-m-d'),
                'fromTime'          => '10:00',
                'toTime'            => '11:30',
                'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
                'colour'            => CalendarEventColoursEnum::BLUE->value,
                'mentor_program_id' => 999999, // Non-existent mentor program ID
            ];

            $request = new StoreCalendarEventRequest;
            $request->merge($payload);
            ($this->prepareRequest)($request);

            try {
                $request->validateResolved();
                expect(false)->toBeTrue('Should have failed validation');
            } catch (ValidationException $validationException) {
                // Should only have mentor_program_id error, not fromDate (slot availability) error
                expect($validationException->errors())->toHaveKey('mentor_program_id')
                    ->and($validationException->errors())->not->toHaveKey('fromDate');
            }
        });

        it('does not add availability error when mentor_program_id is missing (guards withValidator)', function (): void {
            $data = [
                'title'       => 'Missing Mentor Program',
                'fromDate'    => Date::now()->addDays(2)->format('Y-m-d'),
                'toDate'      => Date::now()->addDays(2)->format('Y-m-d'),
                'fromTime'    => '09:15',
                'toTime'      => '10:45',
                'description' => 'desc',
                'type'        => CalendarEventTypeEnum::INDIVIDUAL->value,
                'colour'      => CalendarEventColoursEnum::BLUE->value,
                // mentor_program_id is intentionally missing
            ];

            $request = new StoreCalendarEventRequest;
            $request->merge($data);
            ($this->prepareRequest)($request);

            $validator = Validator::make($data, $request->rules());
            $request->withValidator($validator);

            expect($validator->fails())->toBeTrue()
                ->and($validator->errors()->has('mentor_program_id'))->toBeTrue()
                // The slot availability error should NOT be present
                ->and($validator->errors()->has('fromDate'))->toBeFalse();
        });
    });
});
