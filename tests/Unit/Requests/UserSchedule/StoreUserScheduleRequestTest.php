<?php

declare(strict_types=1);

use App\Enums\RoleEnum;
use App\Enums\UserScheduleRecordType;
use App\Http\Requests\UserSchedule\StoreUserScheduleRequest;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

use function Pest\Laravel\actingAs;

mutates(StoreUserScheduleRequest::class);

describe('StoreUserScheduleRequest validation rules', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        $this->user = User::factory()->create();
        $this->user->assignRole(RoleEnum::MENTOR->value);
        actingAs($this->user);
    });

    it('passes validation with valid working schedule data', function (): void {
        $data = [
            'day_of_week' => 1,
            'start_time'  => '09:00',
            'end_time'    => '17:00',
            'type'        => UserScheduleRecordType::WORKING_DAY->value,
        ];

        $request = new StoreUserScheduleRequest;
        $validator = Validator::make($data, $request->rules());

        expect($validator->passes())->toBeTrue();
    });

    it('passes validation with valid day-off schedule data', function (): void {
        $data = [
            'day_of_week'  => 1,
            'start_time'   => '00:00',
            'end_time'     => '23:59',
            'type'         => UserScheduleRecordType::DAY_OFF->value,
            'day_off_date' => '2025-12-25',
        ];

        $request = new StoreUserScheduleRequest;
        $validator = Validator::make($data, $request->rules());

        expect($validator->passes())->toBeTrue();
    });

    it('allows null day_off_date for working day (nullable rule)', function (): void {
        $data = [
            'day_of_week'  => 2,
            'start_time'   => '09:00',
            'end_time'     => '17:00',
            'type'         => UserScheduleRecordType::WORKING_DAY->value,
            'day_off_date' => null,
        ];

        $request = new StoreUserScheduleRequest;
        $validator = Validator::make($data, $request->rules());

        expect($validator->passes())->toBeTrue();
    });

    it('fails validation when day_of_week is missing', function (): void {
        $data = [
            'start_time' => '09:00',
            'end_time'   => '17:00',
            'type'       => UserScheduleRecordType::WORKING_DAY->value,
        ];

        $request = new StoreUserScheduleRequest;
        $validator = Validator::make($data, $request->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('day_of_week'))->toBeTrue()
            // Ensure custom message key remains configured
            ->and($validator->errors()->first('day_of_week'))
            ->toContain('The day of week field is required.');
    });

    it('fails validation when day_of_week is not between 0 and 6', function (): void {
        $data = [
            'day_of_week' => 7,
            'start_time'  => '09:00',
            'end_time'    => '17:00',
            'type'        => UserScheduleRecordType::WORKING_DAY->value,
        ];

        $request = new StoreUserScheduleRequest;
        $validator = Validator::make($data, $request->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('day_of_week'))->toBeTrue()
            ->and($validator->errors()->first('day_of_week'))
            ->toContain('The day of week field must be between 0 and 6.');
    });

    it('fails validation when start_time is missing', function (): void {
        $data = [
            'day_of_week' => 1,
            'end_time'    => '17:00',
            'type'        => UserScheduleRecordType::WORKING_DAY->value,
        ];

        $request = new StoreUserScheduleRequest;
        $validator = Validator::make($data, $request->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('start_time'))->toBeTrue()
            ->and($validator->errors()->first('start_time'))
            ->toContain('The start time field is required.');
    });

    it('fails validation when end_time is missing', function (): void {
        $data = [
            'day_of_week' => 1,
            'start_time'  => '09:00',
            'type'        => UserScheduleRecordType::WORKING_DAY->value,
        ];

        $request = new StoreUserScheduleRequest;
        $validator = Validator::make($data, $request->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('end_time'))->toBeTrue()
            ->and($validator->errors()->first('end_time'))
            ->toContain('The end time field is required.');
    });

    it('fails validation when end_time is before start_time', function (): void {
        $data = [
            'day_of_week' => 1,
            'start_time'  => '17:00',
            'end_time'    => '09:00',
            'type'        => UserScheduleRecordType::WORKING_DAY->value,
        ];

        $request = new StoreUserScheduleRequest;
        $validator = Validator::make($data, $request->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('end_time'))->toBeTrue()
            ->and($validator->errors()->first('end_time'))
            ->toContain('after start time');
    });

    it('fails validation when type is missing', function (): void {
        $data = [
            'day_of_week' => 1,
            'start_time'  => '09:00',
            'end_time'    => '17:00',
        ];

        $request = new StoreUserScheduleRequest;
        $validator = Validator::make($data, $request->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('type'))->toBeTrue()
            ->and($validator->errors()->first('type'))
            ->toContain('The type field is required.');
    });

    it('fails validation when type is invalid', function (): void {
        $data = [
            'day_of_week' => 1,
            'start_time'  => '09:00',
            'end_time'    => '17:00',
            'type'        => 'invalid_type',
        ];

        $request = new StoreUserScheduleRequest;
        $validator = Validator::make($data, $request->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('type'))->toBeTrue()
            ->and($validator->errors()->first('type'))
            ->toContain('The selected type is invalid.');
    });

    it('fails validation when day_off_date is missing for day-off type', function (): void {
        $data = [
            'day_of_week' => 1,
            'start_time'  => '00:00',
            'end_time'    => '23:59',
            'type'        => UserScheduleRecordType::DAY_OFF->value,
        ];

        $request = new StoreUserScheduleRequest;
        $validator = Validator::make($data, $request->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('day_off_date'))->toBeTrue()
            ->and($validator->errors()->first('day_off_date'))
            ->toContain('required when type is Day off');
    });

    it('passes validation with optional comment', function (): void {
        $data = [
            'day_of_week' => 1,
            'start_time'  => '09:00',
            'end_time'    => '17:00',
            'type'        => UserScheduleRecordType::WORKING_DAY->value,
            'comment'     => 'This is a test comment',
        ];

        $request = new StoreUserScheduleRequest;
        $validator = Validator::make($data, $request->rules());

        expect($validator->passes())->toBeTrue();
    });

    it('fails validation when start_time has invalid format', function (): void {
        $data = [
            'day_of_week' => 1,
            'start_time'  => '9:00',  // Should be HH:MM
            'end_time'    => '17:00',
            'type'        => UserScheduleRecordType::WORKING_DAY->value,
        ];

        $request = new StoreUserScheduleRequest;
        $validator = Validator::make($data, $request->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('start_time'))->toBeTrue()
            ->and($validator->errors()->first('start_time'))
            ->toContain('The start time field must match the format H:i');
    });

    it('fails validation when end_time has invalid format', function (): void {
        $data = [
            'day_of_week' => 1,
            'start_time'  => '09:00',
            'end_time'    => '5:00 PM',  // Should be HH:MM
            'type'        => UserScheduleRecordType::WORKING_DAY->value,
        ];

        $request = new StoreUserScheduleRequest;
        $validator = Validator::make($data, $request->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('end_time'))->toBeTrue()
            ->and($validator->errors()->first('end_time'))
            ->toContain('The end time field must match the format H:i.');
    });

    it('fails validation when day_off_date has invalid format', function (): void {
        $data = [
            'day_of_week'  => 1,
            'start_time'   => '00:00',
            'end_time'     => '23:59',
            'type'         => UserScheduleRecordType::DAY_OFF->value,
            'day_off_date' => '12/25/2025',  // Should be Y-m-d
        ];

        $request = new StoreUserScheduleRequest;
        $validator = Validator::make($data, $request->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('day_off_date'))->toBeTrue()
            ->and($validator->errors()->first('day_off_date'))
            ->toContain('The day off date field must match the format Y-m-d.');
    });

    it('validates day_of_week accepts 0 (Sunday)', function (): void {
        $data = [
            'day_of_week' => 0,
            'start_time'  => '09:00',
            'end_time'    => '17:00',
            'type'        => UserScheduleRecordType::WORKING_DAY->value,
        ];

        $request = new StoreUserScheduleRequest;
        $validator = Validator::make($data, $request->rules());

        expect($validator->passes())->toBeTrue();
    });

    it('validates day_of_week accepts 6 (Saturday)', function (): void {
        $data = [
            'day_of_week' => 6,
            'start_time'  => '09:00',
            'end_time'    => '17:00',
            'type'        => UserScheduleRecordType::WORKING_DAY->value,
        ];

        $request = new StoreUserScheduleRequest;
        $validator = Validator::make($data, $request->rules());

        expect($validator->passes())->toBeTrue();
    });

    it('allows missing day_off_date for non day off types', function (): void {
        actingAs($this->user);

        $this->withoutMiddleware()
            ->postJson(route('user-schedule.batch'),
                [
                    'schedules' => [[
                        'day_of_week' => 1,
                        'start_time'  => '09:00',
                        'end_time'    => '17:00',
                        'type'        => UserScheduleRecordType::WORKING_DAY->value,
                    ]],
                ])->assertStatus(302);
    });
});
describe('StoreUserScheduleRequest', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        $this->user = User::factory()->create();
        Auth::login($this->user);

        $this->prepareRequest = function (StoreUserScheduleRequest $request): void {
            $request->setContainer(app());
            $request->setRedirector(resolve(Redirector::class));
            $request->setUserResolver(fn () => $this->user);
        };
    });

    it('validates day_off_date is required when type is day off', function (): void {
        $request = new StoreUserScheduleRequest;
        $request->merge([
            'day_of_week' => 1,
            'start_time'  => '09:00',
            'end_time'    => '17:00',
            'type'        => UserScheduleRecordType::DAY_OFF->value,
            // Missing day_off_date
        ]);
        ($this->prepareRequest)($request);

        try {
            $request->validateResolved();
            $this->fail('Validation should have failed');
        } catch (ValidationException $validationException) {
            expect($validationException->errors())->toHaveKey('day_off_date')
                ->and($validationException->errors()['day_off_date'][0])
                ->toContain('Day off date is required when type is Day off');
        }
    });

    it('validates day_off_date format when provided', function (): void {
        $request = new StoreUserScheduleRequest;
        $request->merge([
            'day_of_week'  => 1,
            'start_time'   => '09:00',
            'end_time'     => '17:00',
            'type'         => UserScheduleRecordType::DAY_OFF->value,
            'day_off_date' => 'invalid-date',
        ]);
        ($this->prepareRequest)($request);

        try {
            $request->validateResolved();
            $this->fail('Validation should have failed');
        } catch (ValidationException $validationException) {
            expect($validationException->errors())->toHaveKey('day_off_date');
        }
    });

    it('allows valid day_off_date when type is day off', function (): void {
        $request = new StoreUserScheduleRequest;
        $request->merge([
            'day_of_week'  => 1,
            'start_time'   => '09:00',
            'end_time'     => '17:00',
            'type'         => UserScheduleRecordType::DAY_OFF->value,
            'day_off_date' => '2025-06-16',
        ]);
        ($this->prepareRequest)($request);

        expect($request->validateResolved(...))->not->toThrow(ValidationException::class);
    });

    it('does not require day_off_date when type is working day', function (): void {
        $request = new StoreUserScheduleRequest;
        $request->merge([
            'day_of_week' => 1,
            'start_time'  => '09:00',
            'end_time'    => '17:00',
            'type'        => UserScheduleRecordType::WORKING_DAY->value,
            // No day_off_date
        ]);
        ($this->prepareRequest)($request);

        expect($request->validateResolved(...))->not->toThrow(ValidationException::class);
    });

    it('does not add overlap error when schedules do not overlap', function (): void {
        // existing non-overlapping schedule 11:00-12:00
        $this->user->schedules()->create([
            'day_of_week' => 1,
            'start_time'  => '11:00',
            'end_time'    => '12:00',
            'type'        => UserScheduleRecordType::WORKING_DAY->value,
        ]);

        $request = new StoreUserScheduleRequest;
        $request->merge([
            'day_of_week' => 1,
            'start_time'  => '09:00',
            'end_time'    => '10:00',
            'type'        => UserScheduleRecordType::WORKING_DAY->value,
        ]);
        ($this->prepareRequest)($request);

        expect($request->validateResolved(...))->not->toThrow(ValidationException::class);
    });

    it('provides start_time required message via FormRequest', function (): void {
        $request = new StoreUserScheduleRequest;
        $request->merge([
            'day_of_week' => 1,
            // missing start_time
            'end_time' => '17:00',
            'type'     => UserScheduleRecordType::WORKING_DAY->value,
        ]);
        ($this->prepareRequest)($request);

        try {
            $request->validateResolved();
            $this->fail('Validation should have failed');
        } catch (ValidationException $validationException) {
            expect($validationException->errors())->toHaveKey('start_time')
                ->and($validationException->errors()['start_time'][0])->toContain('Start time is required');
        }
    });

    it('provides start_time date_format message via FormRequest', function (): void {
        $request = new StoreUserScheduleRequest;
        $request->merge([
            'day_of_week' => 1,
            'start_time'  => '9:00',
            'end_time'    => '17:00',
            'type'        => UserScheduleRecordType::WORKING_DAY->value,
        ]);
        ($this->prepareRequest)($request);

        try {
            $request->validateResolved();
            $this->fail('Validation should have failed');
        } catch (ValidationException $validationException) {
            expect($validationException->errors())->toHaveKey('start_time')
                ->and($validationException->errors()['start_time'][0])->toContain('HH:MM format');
        }
    });

    it('provides end_time required and format/after messages via FormRequest', function (): void {
        // missing end_time
        $r1 = new StoreUserScheduleRequest;
        $r1->merge([
            'day_of_week' => 1,
            'start_time'  => '09:00',
            'type'        => UserScheduleRecordType::WORKING_DAY->value,
        ]);
        ($this->prepareRequest)($r1);
        try {
            $r1->validateResolved();
            $this->fail('Validation should have failed');
        } catch (ValidationException $validationException) {
            expect($validationException->errors())->toHaveKey('end_time')
                ->and($validationException->errors()['end_time'][0])->toContain('End time is required');
        }

        // invalid format
        $r2 = new StoreUserScheduleRequest;
        $r2->merge([
            'day_of_week' => 1,
            'start_time'  => '09:00',
            'end_time'    => '5:00 PM',
            'type'        => UserScheduleRecordType::WORKING_DAY->value,
        ]);
        ($this->prepareRequest)($r2);
        try {
            $r2->validateResolved();
            $this->fail('Validation should have failed');
        } catch (ValidationException $validationException) {
            expect($validationException->errors())->toHaveKey('end_time')
                ->and($validationException->errors()['end_time'][0])->toContain('HH:MM format');
        }

        // after:start_time message
        $r3 = new StoreUserScheduleRequest;
        $r3->merge([
            'day_of_week' => 1,
            'start_time'  => '17:00',
            'end_time'    => '09:00',
            'type'        => UserScheduleRecordType::WORKING_DAY->value,
        ]);
        ($this->prepareRequest)($r3);
        try {
            $r3->validateResolved();
            $this->fail('Validation should have failed');
        } catch (ValidationException $validationException) {
            expect($validationException->errors())->toHaveKey('end_time')
                ->and($validationException->errors()['end_time'][0])->toContain('after start time');
        }
    });

    it('provides type.in message via FormRequest for invalid type', function (): void {
        $request = new StoreUserScheduleRequest;
        $request->merge([
            'day_of_week' => 1,
            'start_time'  => '09:00',
            'end_time'    => '17:00',
            'type'        => 'invalid',
        ]);
        ($this->prepareRequest)($request);

        try {
            $request->validateResolved();
            $this->fail('Validation should have failed');
        } catch (ValidationException $validationException) {
            expect($validationException->errors())->toHaveKey('type')
                ->and($validationException->errors()['type'][0])->toContain('Invalid schedule type selected');
        }
    });

    it('provides day_of_week.between message via FormRequest', function (): void {
        $request = new StoreUserScheduleRequest;
        $request->merge([
            'day_of_week' => 8,
            'start_time'  => '09:00',
            'end_time'    => '17:00',
            'type'        => UserScheduleRecordType::WORKING_DAY->value,
        ]);
        ($this->prepareRequest)($request);

        try {
            $request->validateResolved();
            $this->fail('Validation should have failed');
        } catch (ValidationException $validationException) {
            expect($validationException->errors())->toHaveKey('day_of_week')
                ->and($validationException->errors()['day_of_week'][0])->toContain('between 0 (Sunday) and 6 (Saturday)');
        }
    });

    it('provides day_of_week.required message via FormRequest', function (): void {
        $request = new StoreUserScheduleRequest;
        $request->merge([
            // missing day_of_week
            'start_time' => '09:00',
            'end_time'   => '17:00',
            'type'       => UserScheduleRecordType::WORKING_DAY->value,
        ]);
        ($this->prepareRequest)($request);

        try {
            $request->validateResolved();
            $this->fail('Validation should have failed');
        } catch (ValidationException $validationException) {
            expect($validationException->errors())->toHaveKey('day_of_week')
                ->and($validationException->errors()['day_of_week'][0])->toContain('Day of week is required');
        }
    });

    it('sets default times during prepareForValidation for day off type', function (): void {
        $request = new StoreUserScheduleRequest;
        $request->merge([
            'day_of_week'  => 2,
            // omit start/end to rely on prepareForValidation
            'type'         => UserScheduleRecordType::DAY_OFF->value,
            'day_off_date' => '2025-12-25',
        ]);
        ($this->prepareRequest)($request);

        // Should be valid because prepareForValidation merges default times
        expect($request->validateResolved(...))->not->toThrow(ValidationException::class);
    });

    it('skips overlap check when there are prior validation errors', function (): void {
        // Missing day_of_week forces early return in withValidator
        $request = new StoreUserScheduleRequest;
        $request->merge([
            // 'day_of_week' missing
            'start_time' => '09:00',
            'end_time'   => '10:00',
            'type'       => UserScheduleRecordType::WORKING_DAY->value,
        ]);
        ($this->prepareRequest)($request);

        try {
            $request->validateResolved();
            $this->fail('Validation should have failed');
        } catch (ValidationException $validationException) {
            // Ensure no overlap error injected on start_time
            expect($validationException->errors())->toHaveKey('day_of_week')
                ->and($validationException->errors())->not->toHaveKey('start_time');
        }
    });

    it('provides type.required message via FormRequest', function (): void {
        $request = new StoreUserScheduleRequest;
        $request->merge([
            'day_of_week' => 1,
            'start_time'  => '09:00',
            'end_time'    => '17:00',
            // missing type
        ]);
        ($this->prepareRequest)($request);

        try {
            $request->validateResolved();
            $this->fail('Validation should have failed');
        } catch (ValidationException $validationException) {
            expect($validationException->errors())->toHaveKey('type')
                ->and($validationException->errors()['type'][0])->toContain('Schedule type is required');
        }
    });

    it('provides day_off_date date_format message via FormRequest for invalid date format', function (): void {
        $request = new StoreUserScheduleRequest;
        $request->merge([
            'day_of_week'  => 2,
            'type'         => UserScheduleRecordType::DAY_OFF->value,
            'day_off_date' => 'not-a-date',
        ]);
        ($this->prepareRequest)($request);

        try {
            $request->validateResolved();
            $this->fail('Validation should have failed');
        } catch (ValidationException $validationException) {
            expect($validationException->errors())->toHaveKey('day_off_date')
                ->and($validationException->errors()['day_off_date'][0])->toContain('match the format Y-m-d');
        }
    });

    it('provides day_off_date.date message via FormRequest when value is not a real date', function (): void {
        $request = new StoreUserScheduleRequest;
        $request->merge([
            'day_of_week'  => 2,
            'type'         => UserScheduleRecordType::DAY_OFF->value,
            // Correct format but invalid calendar date
            'day_off_date' => '2025-02-30',
        ]);
        ($this->prepareRequest)($request);

        try {
            $request->validateResolved();
            $this->fail('Validation should have failed');
        } catch (ValidationException $validationException) {
            expect($validationException->errors())->toHaveKey('day_off_date');
            $msg = $validationException->errors()['day_off_date'][0];
            // Accept either the date or date_format message depending on validator behavior
            expect($msg === null)->toBeFalse();
            $isValidDateMsg = str_contains($msg, 'must be a valid date');
            $isFormatMsg = str_contains($msg, 'match the format Y-m-d');
            expect($isValidDateMsg || $isFormatMsg)->toBeTrue();
        }
    });

    it('adds overlap error when a schedule overlaps for working day', function (): void {
        // Seed an existing schedule 09:30 - 10:30 on Monday (1)
        $this->user->schedules()->create([
            'day_of_week' => 1,
            'start_time'  => '09:30',
            'end_time'    => '10:30',
            'type'        => UserScheduleRecordType::WORKING_DAY->value,
        ]);

        $request = new StoreUserScheduleRequest;
        $request->merge([
            'day_of_week' => 1,
            'start_time'  => '09:00',
            'end_time'    => '10:00',
            'type'        => UserScheduleRecordType::WORKING_DAY->value,
        ]);
        ($this->prepareRequest)($request);

        try {
            $request->validateResolved();
            $this->fail('Validation should have failed due to overlap');
        } catch (ValidationException $validationException) {
            expect($validationException->errors())->toHaveKey('start_time')
                ->and($validationException->errors()['start_time'][0])
                ->toContain('overlaps with an existing schedule');
        }
    });

    it('defines all expected custom validation messages', function (): void {
        $request = new StoreUserScheduleRequest;
        $messages = $request->messages();

        expect($messages)->toHaveKey('day_of_week.required')
            ->and($messages)->toHaveKey('day_of_week.between')
            ->and($messages)->toHaveKey('start_time.required')
            ->and($messages)->toHaveKey('start_time.date_format')
            ->and($messages)->toHaveKey('end_time.required')
            ->and($messages)->toHaveKey('end_time.date_format')
            ->and($messages)->toHaveKey('end_time.after')
            ->and($messages)->toHaveKey('type.required')
            ->and($messages)->toHaveKey('type.in')
            ->and($messages)->toHaveKey('day_off_date.required_if')
            ->and($messages)->toHaveKey('day_off_date.date');
    });

    it('does not add overlap error for DAY_OFF type', function (): void {
        // Existing working schedule on Monday
        $this->user->schedules()->create([
            'day_of_week' => 1,
            'start_time'  => '09:00',
            'end_time'    => '17:00',
            'type'        => UserScheduleRecordType::WORKING_DAY->value,
        ]);

        $request = new StoreUserScheduleRequest;
        $request->merge([
            'day_of_week'  => 1,
            'type'         => UserScheduleRecordType::DAY_OFF->value,
            'day_off_date' => '2025-12-25',
        ]);
        ($this->prepareRequest)($request);

        // prepareForValidation should set times and withValidator should skip overlap
        expect($request->validateResolved(...))->not->toThrow(ValidationException::class);
    });
});
