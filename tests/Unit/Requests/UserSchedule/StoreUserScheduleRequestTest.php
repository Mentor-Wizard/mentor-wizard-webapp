<?php

declare(strict_types=1);

use App\Enums\UserScheduleRecordType;
use App\Http\Requests\UserSchedule\StoreUserScheduleRequest;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Validator;

use function Pest\Laravel\actingAs;

mutates(StoreUserScheduleRequest::class);

describe('StoreUserScheduleRequest validation rules', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        $this->user = User::factory()->create();
        actingAs($this->user);
    });

    it('passes validation with valid working schedule data', function (): void {
        $data = [
            'day_of_week' => 1,
            'start_time'  => '09:00',
            'end_time'    => '17:00',
            'type'        => UserScheduleRecordType::WORKING_DAY->value,
            'timezone'    => $this->user->profile->timezone,
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
            'timezone'     => $this->user->profile->timezone,
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

    it('fails validation when timezone is missing', function (): void {
        $data = [
            'day_of_week' => 1,
            'start_time'  => '09:00',
            'end_time'    => '17:00',
            'type'        => UserScheduleRecordType::WORKING_DAY->value,
        ];

        $request = new StoreUserScheduleRequest;
        $validator = Validator::make($data, $request->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('timezone'))->toBeTrue();
    });

    it('passes validation with optional comment', function (): void {
        $data = [
            'day_of_week' => 1,
            'start_time'  => '09:00',
            'end_time'    => '17:00',
            'type'        => UserScheduleRecordType::WORKING_DAY->value,
            'timezone'    => $this->user->profile->timezone,
            'comment'     => 'This is a test comment',
        ];

        $request = new StoreUserScheduleRequest;
        $validator = Validator::make($data, $request->rules());

        expect($validator->passes())->toBeTrue();
    });
});
