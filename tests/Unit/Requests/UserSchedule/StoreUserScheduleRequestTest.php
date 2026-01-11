<?php

declare(strict_types=1);

use App\Enums\RoleEnum;
use App\Enums\UserScheduleRecordType;
use App\Http\Requests\UserSchedule\StoreUserScheduleRequest;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Routing\Redirector;
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

    it('fails validation when day_of_week is missing', function (): void {
        $data = [
            'start_time' => '09:00',
            'end_time'   => '17:00',
            'type'       => UserScheduleRecordType::WORKING_DAY->value,
        ];

        $request = new StoreUserScheduleRequest;
        $validator = Validator::make($data, $request->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('day_of_week'))->toBeTrue();
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
            ->and($validator->errors()->has('day_of_week'))->toBeTrue();
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
            ->and($validator->errors()->has('start_time'))->toBeTrue();
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
            ->and($validator->errors()->has('end_time'))->toBeTrue();
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
            ->and($validator->errors()->has('end_time'))->toBeTrue();
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
            ->and($validator->errors()->has('type'))->toBeTrue();
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
            ->and($validator->errors()->has('type'))->toBeTrue();
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
            ->and($validator->errors()->has('day_off_date'))->toBeTrue();
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
            ->and($validator->errors()->has('start_time'))->toBeTrue();
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
            ->and($validator->errors()->has('end_time'))->toBeTrue();
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
            ->and($validator->errors()->has('day_off_date'))->toBeTrue();
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
});
