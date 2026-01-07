<?php

declare(strict_types=1);
use App\Enums\CalendarEventColoursEnum;
use App\Enums\CalendarEventStatusEnum;
use App\Enums\CalendarEventTypeEnum;
use App\Enums\RoleEnum;
use App\Http\Requests\Calendar\EditCalendarEventRequest;
use App\Models\CalendarEvent;
use App\Models\MentorProgram;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Date;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

mutates(EditCalendarEventRequest::class);

describe('EditCalendarEventRequest rules and withValidator guards', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        $this->user = User::factory()->create();
        auth()->login($this->user);
        $this->user->assignRole(Role::findByName(RoleEnum::MENTOR->value));

        $this->mentorProgram = MentorProgram::factory()->create([
            'mentor_id' => $this->user->getKey(),
        ]);
        $this->prepareRequest = function (EditCalendarEventRequest $request): void {
            $request->setContainer(app());
            $request->setRedirector(resolve(Redirector::class));
            $request->setUserResolver(fn () => $this->user);
        };

        // Create an overlapping calendar event for the authenticated user
        $start = Date::now()->addDays(5)->setTime(10, 0, 0);
        $end = Date::now()->addDays(5)->setTime(11, 30, 0);
        $this->event = CalendarEvent::factory()->create([
            'start_date_time'   => $start->copy(),
            'end_date_time'     => $end->copy(),
            'date'              => $start->copy()->format('Y-m-d'),
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'title'             => 'existing overlap',
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);
        $this->user->calendarEvents()->attach($this->event->getKey(), [
            'colour' => CalendarEventColoursEnum::BLUE->value,
        ]);

        $ystStart = Date::now()->subDay()->setTime(10, 0, 0);
        $ystEnd = Date::now()->subDay()->setTime(11, 30, 0);
        $ystEvent = CalendarEvent::factory()->create([
            'start_date_time'   => $ystStart->copy(),
            'end_date_time'     => $ystEnd->copy(),
            'date'              => $ystStart->copy()->format('Y-m-d'),
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'title'             => 'yesterday overlap',
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);
        $this->user->calendarEvents()->attach($ystEvent->getKey(), [
            'colour' => CalendarEventColoursEnum::BLUE->value,
        ]);
    });

    it('successfully validates payload with all required info', function (): void {
        $payload = [
            'title'             => 'successful validation',
            'fromDate'          => Date::now()->addDays(5)->format('Y-m-d'),
            'toDate'            => Date::now()->addDays(5)->format('Y-m-d'),
            'fromTime'          => '10:00',
            'toTime'            => '11:30',
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'colour'            => CalendarEventColoursEnum::BLUE->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ];

        $request = new EditCalendarEventRequest;

        $validator = Validator::make($payload, $request->rules());

        expect($validator->passes())->toBeTrue();
        expect($validator->errors())->isEmpty();
    });

    it('rejects when title is missing', function (): void {
        $payload = [
            // 'title' => missing
            'fromDate'          => Date::now()->addDays(5)->format('Y-m-d'),
            'toDate'            => Date::now()->addDays(5)->format('Y-m-d'),
            'fromTime'          => '10:00',
            'toTime'            => '11:30',
            'type'              => 'individual',
            'colour'            => CalendarEventColoursEnum::BLUE->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ];

        $request = new EditCalendarEventRequest;

        $validator = Validator::make($payload, $request->rules());

        expect($validator->passes())->toBeFalse();
        expect($validator->errors()->has('title'))->toBeTrue();
    });

    it('rejects when fromDate is wrong format', function (): void {
        $payload = [
            'title'             => 'Wrong fromDate format ',
            'fromDate'          => '2025-01----01',
            'toDate'            => Date::now()->addDays(5)->format('Y-m-d'),
            'fromTime'          => '10:00',
            'toTime'            => '11:30',
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'colour'            => CalendarEventColoursEnum::BLUE->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ];

        $request = new EditCalendarEventRequest;

        $validator = Validator::make($payload, $request->rules());

        expect($validator->passes())->toBeFalse();
        expect($validator->errors()->has('fromDate'))->toBeTrue();
    });

    it('rejects when fromTime is wrong format', function (): void {
        $payload = [
            'title'             => 'Wrong fromDate format ',
            'fromDate'          => Date::now()->addDays(5)->format('Y-m-d'),
            'toDate'            => Date::now()->addDays(5)->format('Y-m-d'),
            'fromTime'          => '--:00',
            'toTime'            => '11:30',
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'colour'            => CalendarEventColoursEnum::BLUE->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ];

        $request = new EditCalendarEventRequest;

        $validator = Validator::make($payload, $request->rules());

        expect($validator->passes())->toBeFalse();
        expect($validator->errors()->has('fromTime'))->toBeTrue();
    });

    it('rejects when toDate is wrong format', function (): void {
        $payload = [
            'title'             => 'Wrong fromDate format ',
            'fromDate'          => Date::now()->addDays(5)->format('Y-m-d'),
            'toDate'            => '----01-01',
            'fromTime'          => '10:00',
            'toTime'            => '11:30',
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'colour'            => CalendarEventColoursEnum::BLUE->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ];

        $request = new EditCalendarEventRequest;

        $validator = Validator::make($payload, $request->rules());

        expect($validator->passes())->toBeFalse();
        expect($validator->errors()->has('toDate'))->toBeTrue();
    });

    it('rejects when toTime is wrong format', function (): void {
        $payload = [
            'title'             => 'Wrong fromDate format ',
            'fromDate'          => Date::now()->addDays(5)->format('Y-m-d'),
            'toDate'            => Date::now()->addDays(5)->format('Y-m-d'),
            'fromTime'          => '10:00',
            'toTime'            => '--:30',
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'colour'            => CalendarEventColoursEnum::BLUE->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ];

        $request = new EditCalendarEventRequest;

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
            'colour'            => 'wrong colour',
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ];

        $request = new EditCalendarEventRequest;

        $validator = Validator::make($payload, $request->rules());

        expect($validator->passes())->toBeFalse();
        expect($validator->errors()->has('colour'))->toBeTrue();
    });

    it('adds fromDate error from withValidator
    when times overlap (after-callback executed)', function (): void {
        $data = [
            'id'                => 123,
            'title'             => 'Overlap test',
            'fromDate'          => Date::now()->addDays(5)->format('Y-m-d'),
            'toDate'            => Date::now()->addDays(5)->format('Y-m-d'),
            'fromTime'          => '10:00',
            'toTime'            => '11:00',
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'colour'            => CalendarEventColoursEnum::BLUE->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ];

        $request = new EditCalendarEventRequest;
        $request->merge($data);
        ($this->prepareRequest)($request);

        $validator = Validator::make($data, $request->rules());
        $request->withValidator($validator);

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('fromDate'))->toBeTrue();
    });

    it('runs after-callback without adding error
        when times are available (ensures both date concatenations execute)', function (): void {
        $date = Date::now()->addDays(5)->format('Y-m-d');
        // Choose a free slot before the existing 10:00-11:30 event
        $data = [
            'id'                => 999,
            'title'             => 'No overlap slot',
            'fromDate'          => $date,
            'toDate'            => $date,
            'fromTime'          => '08:00',
            'toTime'            => '08:30',
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'colour'            => CalendarEventColoursEnum::BLUE->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ];

        $request = new EditCalendarEventRequest;
        $request->merge($data);
        ($this->prepareRequest)($request);

        $validator = Validator::make($data, $request->rules());
        $request->withValidator($validator);

        expect($validator->fails())->toBeFalse()
            ->and($validator->errors()->has('fromDate'))->toBeFalse();
    });

    it('skips withValidator when fromTime invalid (guards in hasAny)', function (): void {
        $data = [
            'id'                => 123,
            'title'             => 'Invalid FromTime',
            'fromDate'          => Date::now()->addDays(5)->format('Y-m-d'),
            'toDate'            => Date::now()->addDays(5)->format('Y-m-d'),
            'fromTime'          => '--:00',
            'toTime'            => '11:30',
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'colour'            => CalendarEventColoursEnum::BLUE->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ];

        $request = new EditCalendarEventRequest;
        $request->merge($data);
        ($this->prepareRequest)($request);

        $validator = Validator::make($data, $request->rules());
        $request->withValidator($validator);

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('fromTime'))->toBeTrue()
            ->and($validator->errors()->has('fromDate'))->toBeFalse();
    });

    it('skips withValidator when fromTime invalid but parseable by concatenation', function (): void {
        $data = [
            'id'                => 123,
            'title'             => 'Leading space FromTime',
            'fromDate'          => Date::now()->addDays(5)->format('Y-m-d'),
            'toDate'            => Date::now()->addDays(5)->format('Y-m-d'),
            'fromTime'          => ' 10:00',
            'toTime'            => '11:30',
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'colour'            => CalendarEventColoursEnum::BLUE->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ];

        $request = new EditCalendarEventRequest;
        $request->merge($data);
        ($this->prepareRequest)($request);

        $validator = Validator::make($data, $request->rules());
        $request->withValidator($validator);

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('fromTime'))->toBeTrue()
            ->and($validator->errors()->has('fromDate'))->toBeFalse();
    });

    it('does not add availability error when fromDate is invalid (guards withValidator)', function (): void {
        $data = [
            'title'             => 'Invalid FromDate (yesterday)',
            'fromDate'          => Date::now()->subDay()->format('Y-m-d'), // parseable but invalid by rule
            'toDate'            => Date::now()->addDays(5)->format('Y-m-d'),
            'fromTime'          => '10:00',
            'toTime'            => '11:30',
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'colour'            => CalendarEventColoursEnum::BLUE->value,
            'id'                => 1,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ];

        $request = new EditCalendarEventRequest;
        $request->merge($data);
        ($this->prepareRequest)($request);

        $validator = Validator::make($data, $request->rules());
        $request->withValidator($validator);

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('fromDate'))->toBeTrue()
            // Only the base rule error should be present; withValidator must be skipped
            ->and(count($validator->errors()->get('fromDate')))->toBe(1);
    });

    it('does not add availability error when fromTime is invalid (guards withValidator)', function (): void {
        $data = [
            'title'             => 'Invalid FromTime',
            'fromDate'          => Date::now()->addDays(5)->format('Y-m-d'),
            'toDate'            => Date::now()->addDays(5)->format('Y-m-d'),
            'fromTime'          => '--:00',
            'toTime'            => '11:30',
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'colour'            => CalendarEventColoursEnum::BLUE->value,
            'id'                => 1,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ];

        $request = new EditCalendarEventRequest;
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
            'fromDate'          => Date::now()->addDays(5)->format('Y-m-d'),
            'toDate'            => '----01-01',
            'fromTime'          => '10:00',
            'toTime'            => '11:30',
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'colour'            => CalendarEventColoursEnum::BLUE->value,
            'id'                => 1,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ];

        $request = new EditCalendarEventRequest;
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
            'fromDate'          => Date::now()->addDays(5)->format('Y-m-d'),
            'toDate'            => Date::now()->addDays(5)->format('Y-m-d'),
            'fromTime'          => '10:00',
            'toTime'            => '--:30',
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'colour'            => CalendarEventColoursEnum::BLUE->value,
            'id'                => 1,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ];

        $request = new EditCalendarEventRequest;
        $request->merge($data);
        ($this->prepareRequest)($request);

        $validator = Validator::make($data, $request->rules());
        $request->withValidator($validator);

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('toTime'))->toBeTrue()
            ->and($validator->errors()->has('fromDate'))->toBeFalse();
    });

    it('ignores current event id during availability check (editing same times)', function (): void {
        // Using the same times as the existing event should not produce an error when editing that event
        $date = Date::now()->addDays(5)->format('Y-m-d');
        $data = [
            'id'                => $this->event->getKey(),
            'title'             => 'Edit same time',
            'fromDate'          => $date,
            'toDate'            => $date,
            'fromTime'          => '10:00',
            'toTime'            => '11:30',
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'colour'            => CalendarEventColoursEnum::BLUE->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ];

        $request = new EditCalendarEventRequest;
        $request->merge($data);
        ($this->prepareRequest)($request);

        $validator = Validator::make($data, $request->rules());
        $request->withValidator($validator);

        // No fromDate error should be added because the current event is excluded by ID
        expect($validator->fails())->toBeFalse()
            ->and($validator->errors()->has('fromDate'))->toBeFalse();
    });

    it('verify slots availability with wrong fromTime validation from list', function (): void {
        $newEvent = CalendarEvent::query()->create([
            'title'             => 'Busy block',
            'status'            => CalendarEventStatusEnum::CONFIRMED,
            'start_date_time'   => Date::now()->addDays(5)->setTime(10, 0),
            'end_date_time'     => Date::now()->addDays(5)->setTime(11, 30),
            'date'              => Date::now()->addDays(5)->format('Y-m-d'),
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'description'       => 'Busy',
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);
        $newEvent->calendarEventUsers()->attach($this->user->getKey());

        $payload = [
            'id'                => $this->event->getKey(),
            'title'             => 'Conflicting slot',
            'fromDate'          => Date::now()->addDays(5)->format('Y-m-d'),
            'toDate'            => Date::now()->addDays(5)->format('Y-m-d'),
            'fromTime'          => null,
            'toTime'            => '11:30',
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'colour'            => CalendarEventColoursEnum::BLUE->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ];

        $request = new EditCalendarEventRequest;
        $request->merge($payload);
        ($this->prepareRequest)($request);

        try {
            $request->validateResolved();
            expect(false)->toBeTrue('Should have failed validation due to slot conflict');
        } catch (ValidationException $validationException) {
            expect($validationException->errors())->toHaveKey('fromTime')
                ->and($validationException->errors()['fromTime'])->toContain('Start time is required.');
        }

        $payload = [
            'id'                => $this->event->getKey(),
            'title'             => 'Conflicting slot',
            'fromDate'          => Date::now()->addDays(5)->format('Y-m-d'),
            'toDate'            => Date::now()->addDays(5)->format('Y-m-d'),
            'fromTime'          => '10:00',
            'toTime'            => '11:30',
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'colour'            => CalendarEventColoursEnum::BLUE->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ];

        $request = new EditCalendarEventRequest;
        $request->merge($payload);
        ($this->prepareRequest)($request);

        try {
            $request->validateResolved();
            expect(false)->toBeTrue('Should have failed validation due to slot conflict');
        } catch (ValidationException $validationException) {
            expect($validationException->errors())->toHaveKey('fromDate')
                ->and($validationException->errors()['fromDate'])
                ->toContain('there are another events on this time');
        }
    });

});
