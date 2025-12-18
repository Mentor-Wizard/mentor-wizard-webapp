<?php

declare(strict_types=1);

use App\Enums\UserScheduleRecordType;
use App\Http\Requests\UserSchedule\StoreBatchUserScheduleRequest;
use App\Models\User;
use App\Models\UserSchedule;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Validator;

use function Pest\Laravel\actingAs;

mutates(StoreBatchUserScheduleRequest::class);

describe('StoreBatchUserScheduleRequest validation rules', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        $this->user = User::factory()->create();
        actingAs($this->user);
    });

    it('passes validation with valid batch data', function (): void {
        $data = [
            'schedules' => [
                [
                    'day_of_week' => 1,
                    'start_time'  => '09:00',
                    'end_time'    => '12:00',
                    'type'        => UserScheduleRecordType::WORKING_DAY->value,
                ],
            ],
            'delete_ids' => [],
        ];

        $request = new StoreBatchUserScheduleRequest;
        $validator = Validator::make($data, $request->rules());

        expect($validator->passes())->toBeTrue();
    });

    it('passes validation with empty schedules and delete_ids', function (): void {
        $data = [
            'schedules'  => [],
            'delete_ids' => [],
        ];

        $request = new StoreBatchUserScheduleRequest;
        $validator = Validator::make($data, $request->rules());

        expect($validator->passes())->toBeTrue();
    });

    it('fails validation when schedules is not an array', function (): void {
        $data = [
            'schedules'  => 'not-an-array',
            'delete_ids' => [],
        ];

        $request = new StoreBatchUserScheduleRequest;
        $validator = Validator::make($data, $request->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('schedules'))->toBeTrue();
    });

    it('fails validation when delete_ids is not an array', function (): void {
        $data = [
            'schedules'  => [],
            'delete_ids' => 'not-an-array',
        ];

        $request = new StoreBatchUserScheduleRequest;
        $validator = Validator::make($data, $request->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('delete_ids'))->toBeTrue();
    });

    it('fails validation when schedule day_of_week is missing', function (): void {
        $data = [
            'schedules' => [
                [
                    'start_time' => '09:00',
                    'end_time'   => '12:00',
                    'type'       => UserScheduleRecordType::WORKING_DAY->value,
                    'timezone'   => 'UTC',
                ],
            ],
            'delete_ids' => [],
        ];

        $request = new StoreBatchUserScheduleRequest;
        $validator = Validator::make($data, $request->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('schedules.0.day_of_week'))->toBeTrue();
    });

    it('fails validation when schedule start_time is missing', function (): void {
        $data = [
            'schedules' => [
                [
                    'day_of_week' => 1,
                    'end_time'    => '12:00',
                    'type'        => UserScheduleRecordType::WORKING_DAY->value,

                ],
            ],
            'delete_ids' => [],
        ];

        $request = new StoreBatchUserScheduleRequest;
        $validator = Validator::make($data, $request->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('schedules.0.start_time'))->toBeTrue();
    });

    it('fails validation when schedule end_time is before start_time', function (): void {
        $data = [
            'schedules' => [
                [
                    'day_of_week' => 1,
                    'start_time'  => '17:00',
                    'end_time'    => '09:00',
                    'type'        => UserScheduleRecordType::WORKING_DAY->value,

                ],
            ],
            'delete_ids' => [],
        ];

        $request = new StoreBatchUserScheduleRequest;
        $validator = Validator::make($data, $request->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('schedules.0.end_time'))->toBeTrue();
    });

    it('fails validation when schedule type is invalid', function (): void {
        $data = [
            'schedules' => [
                [
                    'day_of_week' => 1,
                    'start_time'  => '09:00',
                    'end_time'    => '12:00',
                    'type'        => 'invalid_type',

                ],
            ],
            'delete_ids' => [],
        ];

        $request = new StoreBatchUserScheduleRequest;
        $validator = Validator::make($data, $request->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('schedules.0.type'))->toBeTrue();
    });

    it('passes validation when schedule has optional id', function (): void {
        $schedule = UserSchedule::factory()->create([
            'user_id'     => $this->user->getKey(),
            'day_of_week' => 1,
            'start_time'  => '09:00',
            'end_time'    => '12:00',
            'type'        => UserScheduleRecordType::WORKING_DAY->value,
        ]);

        $data = [
            'schedules' => [
                [
                    'id'          => $schedule->getKey(),
                    'day_of_week' => 1,
                    'start_time'  => '10:00',
                    'end_time'    => '13:00',
                    'type'        => UserScheduleRecordType::WORKING_DAY->value,
                ],
            ],
            'delete_ids' => [],
        ];

        $request = new StoreBatchUserScheduleRequest;
        $validator = Validator::make($data, $request->rules());

        expect($validator->passes())->toBeTrue();
    });

    it('passes validation with multiple schedules', function (): void {
        $data = [
            'schedules' => [
                [
                    'day_of_week' => 1,
                    'start_time'  => '09:00',
                    'end_time'    => '12:00',
                    'type'        => UserScheduleRecordType::WORKING_DAY->value,

                ],
                [
                    'day_of_week' => 2,
                    'start_time'  => '13:00',
                    'end_time'    => '17:00',
                    'type'        => UserScheduleRecordType::WORKING_DAY->value,

                ],
            ],
            'delete_ids' => [],
        ];

        $request = new StoreBatchUserScheduleRequest;
        $validator = Validator::make($data, $request->rules());

        expect($validator->passes())->toBeTrue();
    });

    it('passes validation with day-off schedule', function (): void {
        $data = [
            'schedules' => [
                [
                    'day_of_week'  => 1,
                    'start_time'   => '00:00',
                    'end_time'     => '23:59',
                    'type'         => UserScheduleRecordType::DAY_OFF->value,
                    'day_off_date' => Date::now()->addMonth()->format('Y-m-d'),
                    'timezone'     => 'UTC',
                ],
            ],
            'delete_ids' => [],
        ];

        $request = new StoreBatchUserScheduleRequest;
        $validator = Validator::make($data, $request->rules());

        expect($validator->passes())->toBeTrue();
    });
});

describe('StoreBatchUserScheduleRequest custom validation - ownership', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        $this->user = User::factory()->create();
        actingAs($this->user);
    });

    it('allows deleting own schedule', function (): void {
        $schedule = UserSchedule::factory()->create([
            'user_id'     => $this->user->getKey(),
            'day_of_week' => 1,
            'start_time'  => '09:00',
            'end_time'    => '12:00',
            'type'        => UserScheduleRecordType::WORKING_DAY->value,

        ]);

        $data = [
            'schedules'  => [],
            'delete_ids' => [$schedule->getKey()],
        ];

        $request = StoreBatchUserScheduleRequest::create(
            route('user-schedule.batch'),
            'POST',
            $data
        );
        $request->setUserResolver(fn () => $this->user);

        $validator = Validator::make($data, $request->rules());
        $request->withValidator($validator);
        $validator->validate();

        expect($validator->errors()->has('delete_ids'))->toBeFalse();
    });

    it('allows updating own schedule', function (): void {
        $schedule = UserSchedule::factory()->create([
            'user_id'     => $this->user->getKey(),
            'day_of_week' => 1,
            'start_time'  => '09:00',
            'end_time'    => '12:00',
            'type'        => UserScheduleRecordType::WORKING_DAY->value,

        ]);

        $data = [
            'schedules' => [
                [
                    'id'          => $schedule->getKey(),
                    'day_of_week' => 1,
                    'start_time'  => '10:00',
                    'end_time'    => '13:00',
                    'type'        => UserScheduleRecordType::WORKING_DAY->value,

                ],
            ],
            'delete_ids' => [],
        ];

        $request = StoreBatchUserScheduleRequest::create(
            route('user-schedule.batch'),
            'POST',
            $data
        );
        $request->setUserResolver(fn () => $this->user);

        $validator = Validator::make($data, $request->rules());
        $request->withValidator($validator);
        $validator->validate();

        expect($validator->errors()->has('schedules.0.id'))->toBeFalse();
    });
});

describe('StoreBatchUserScheduleRequest custom validation - overlap detection', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        $this->user = User::factory()->create();
        actingAs($this->user);
    });

    it('detects overlapping schedules within the batch', function (): void {
        $data = [
            'schedules' => [
                [
                    'day_of_week' => 1,
                    'start_time'  => '09:00',
                    'end_time'    => '13:00',
                    'type'        => UserScheduleRecordType::WORKING_DAY->value,

                ],
                [
                    'day_of_week' => 1,
                    'start_time'  => '12:00',
                    'end_time'    => '16:00',
                    'type'        => UserScheduleRecordType::WORKING_DAY->value,

                ],
            ],
            'delete_ids' => [],
        ];

        $request = StoreBatchUserScheduleRequest::create(
            route('user-schedule.batch'),
            'POST',
            $data
        );
        $request->setUserResolver(fn () => $this->user);

        $validator = Validator::make($data, $request->rules());
        $request->withValidator($validator);

        expect($validator->errors()->has('schedules.1.start_time'))
            ->toBeTrue();
    });

    it('allows non-overlapping schedules within the batch', function (): void {
        $data = [
            'schedules' => [
                [
                    'day_of_week' => 0,
                    'start_time'  => '09:00',
                    'end_time'    => '12:00',
                    'type'        => UserScheduleRecordType::WORKING_DAY->value,

                ],
                [
                    'day_of_week' => 0,
                    'start_time'  => '13:00',
                    'end_time'    => '16:00',
                    'type'        => UserScheduleRecordType::WORKING_DAY->value,

                ],
            ],
            'delete_ids' => [],
        ];

        $request = StoreBatchUserScheduleRequest::create(
            route('user-schedule.batch'),
            'POST',
            $data
        );
        $request->setUserResolver(fn () => $this->user);

        $validator = Validator::make($data, $request->rules());
        $request->withValidator($validator);
        $validator->validate();

        expect($validator->errors()->has('schedules.0.start_time'))->toBeFalse();
    });

    it('detects overlap with existing schedules', function (): void {
        UserSchedule::factory()->create([
            'user_id'     => $this->user->getKey(),
            'day_of_week' => 0,
            'start_time'  => '09:00',
            'end_time'    => '12:00',
            'type'        => UserScheduleRecordType::WORKING_DAY->value,

        ]);

        $data = [
            'schedules' => [
                [
                    'day_of_week' => 0,
                    'start_time'  => '11:00',
                    'end_time'    => '14:00',
                    'type'        => UserScheduleRecordType::WORKING_DAY->value,

                ],
            ],
            'delete_ids' => [],
        ];

        $request = StoreBatchUserScheduleRequest::create(
            route('user-schedule.batch'),
            'POST',
            $data
        );
        $request->setUserResolver(fn () => $this->user);

        $validator = Validator::make($data, $request->rules());
        $request->withValidator($validator);

        expect($validator->errors()->has('schedules.0.start_time'))->toBeTrue();
    });

    it('allows updating schedule to new time slot', function (): void {
        $schedule = UserSchedule::factory()->create([
            'user_id'     => $this->user->getKey(),
            'day_of_week' => 1,
            'start_time'  => '09:00',
            'end_time'    => '12:00',
            'type'        => UserScheduleRecordType::WORKING_DAY->value,

        ]);

        $data = [
            'schedules' => [
                [
                    'id'          => $schedule->getKey(),
                    'day_of_week' => 1,
                    'start_time'  => '13:00',
                    'end_time'    => '16:00',
                    'type'        => UserScheduleRecordType::WORKING_DAY->value,

                ],
            ],
            'delete_ids' => [],
        ];

        $request = StoreBatchUserScheduleRequest::create(
            route('user-schedule.batch'),
            'POST',
            $data
        );
        $request->setUserResolver(fn () => $this->user);

        $validator = Validator::make($data, $request->rules());
        $request->withValidator($validator);
        $validator->validate();

        expect($validator->errors()->has('schedules.0.start_time'))->toBeFalse();
    });

    it('ignores deleted schedules in overlap detection', function (): void {
        $existingSchedule = UserSchedule::factory()->create([
            'user_id'     => $this->user->getKey(),
            'day_of_week' => 1,
            'start_time'  => '09:00',
            'end_time'    => '12:00',
            'type'        => UserScheduleRecordType::WORKING_DAY->value,

        ]);

        $data = [
            'schedules' => [
                [
                    'day_of_week' => 1,
                    'start_time'  => '10:00',
                    'end_time'    => '14:00',
                    'type'        => UserScheduleRecordType::WORKING_DAY->value,

                ],
            ],
            'delete_ids' => [$existingSchedule->getKey()],
        ];

        $request = StoreBatchUserScheduleRequest::create(
            route('user-schedule.batch'),
            'POST',
            $data
        );
        $request->setUserResolver(fn () => $this->user);

        $validator = Validator::make($data, $request->rules());
        $request->withValidator($validator);
        $validator->validate();

        expect($validator->errors()->has('schedules.0.start_time'))->toBeFalse();
    });

    it('skips overlap check for day-off schedules', function (): void {
        UserSchedule::factory()->create([
            'user_id'     => $this->user->getKey(),
            'day_of_week' => 1,
            'start_time'  => '09:00',
            'end_time'    => '17:00',
            'type'        => UserScheduleRecordType::WORKING_DAY->value,

        ]);

        $data = [
            'schedules' => [
                [
                    'day_of_week'  => 1,
                    'start_time'   => '00:00',
                    'end_time'     => '23:59',
                    'type'         => UserScheduleRecordType::DAY_OFF->value,
                    'day_off_date' => Date::now()->addMonth()->format('Y-m-d'),
                    'timezone'     => 'UTC',
                ],
            ],
            'delete_ids' => [],
        ];

        $request = StoreBatchUserScheduleRequest::create(
            route('user-schedule.batch'),
            'POST',
            $data
        );
        $request->setUserResolver(fn () => $this->user);

        $validator = Validator::make($data, $request->rules());
        $request->withValidator($validator);
        $validator->validate();

        expect($validator->errors()->has('schedules.0.start_time'))->toBeFalse();
    });

    it('allows schedules on different days', function (): void {
        $data = [
            'schedules' => [
                [
                    'day_of_week' => 1,
                    'start_time'  => '09:00',
                    'end_time'    => '13:00',
                    'type'        => UserScheduleRecordType::WORKING_DAY->value,

                ],
                [
                    'day_of_week' => 2,
                    'start_time'  => '09:00',
                    'end_time'    => '13:00',
                    'type'        => UserScheduleRecordType::WORKING_DAY->value,

                ],
            ],
            'delete_ids' => [],
        ];

        $request = StoreBatchUserScheduleRequest::create(
            route('user-schedule.batch'),
            'POST',
            $data
        );
        $request->setUserResolver(fn () => $this->user);

        $validator = Validator::make($data, $request->rules());
        $request->withValidator($validator);
        $validator->validate();

        expect($validator->errors()->has('schedules.0.start_time'))->toBeFalse();
    });
});
