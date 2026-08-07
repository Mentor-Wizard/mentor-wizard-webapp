<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Modules\UserSchedule\Enums\UserScheduleRecordType;
use Modules\UserSchedule\Models\UserSchedule;
use Modules\UserSchedule\Services\CheckUserScheduleOverlap;

mutates(CheckUserScheduleOverlap::class);

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
});

describe('CheckUserScheduleOverlap', function (): void {
    it('returns error with the exact message when schedules per day exceed the maximum', function (): void {
        $user = User::factory()->create();

        $schedules = [];
        for ($i = 0; $i < UserSchedule::MAX_NUMBER_OF_SCHEDULES_PERIODS_PER_DAY + 1; $i++) {
            $start = sprintf('%02d:00', 8 + $i);
            $end = sprintf('%02d:00', 9 + $i);
            $schedules[] = [
                'day_of_week' => 1,
                'start_time'  => $start,
                'end_time'    => $end,
                'type'        => UserScheduleRecordType::WORKING_DAY->value,
            ];
        }

        $errors = new CheckUserScheduleOverlap($schedules, [], $user->getKey())->verifyOverlappingErrors();

        $flattened = [];
        foreach ($errors as $pair) {
            foreach ($pair as $key => $message) {
                $flattened[$key] = $message;
            }
        }

        expect($flattened)->toHaveKey('schedules.1.0.max_schedules_per_day')
            ->and($flattened['schedules.1.0.max_schedules_per_day'])->toBe(
                'There are more than '.UserSchedule::MAX_NUMBER_OF_SCHEDULES_PERIODS_PER_DAY.'  schedules for this day'
            );
    });

    it('does not error when schedules per day exactly equal the maximum', function (): void {
        $user = User::factory()->create();

        $schedules = [];
        for ($i = 0; $i < UserSchedule::MAX_NUMBER_OF_SCHEDULES_PERIODS_PER_DAY; $i++) {
            $start = sprintf('%02d:00', 8 + $i);
            $end = sprintf('%02d:00', 9 + $i);
            $schedules[] = [
                'day_of_week' => 1,
                'start_time'  => $start,
                'end_time'    => $end,
                'type'        => UserScheduleRecordType::WORKING_DAY->value,
            ];
        }

        $errors = new CheckUserScheduleOverlap($schedules, [], $user->getKey())->verifyOverlappingErrors();

        expect($errors)->toBeArray()->toBeEmpty();
    });

    it('returns error with the exact message when day off exclusions exceed the maximum', function (): void {
        $user = User::factory()->create();

        $schedules = [];
        for ($i = 0; $i < UserSchedule::MAX_NUMBER_OF_ACTIVE_DAY_OFF_EXCLUSIONS + 1; $i++) {
            $schedules[] = [
                'type'          => UserScheduleRecordType::DAY_OFF->value,
                'day_off_date'  => now()->addDays($i + 1)->toDateString(),
                'day_of_week'   => 1,
                'start_time'    => '00:00',
                'end_time'      => '23:59',
            ];
        }

        $errors = new CheckUserScheduleOverlap($schedules, [], $user->getKey())->verifyOverlappingErrors();

        $flattened = [];
        foreach ($errors as $pair) {
            foreach ($pair as $key => $message) {
                $flattened[$key] = $message;
            }
        }

        // 7 is the special index used in Vue component for exclusions bucket
        expect($flattened)->toHaveKey('schedules.7.max_schedules_per_day')
            ->and($flattened['schedules.7.max_schedules_per_day'])->toBe(
                'There are more than '.UserSchedule::MAX_NUMBER_OF_SCHEDULES_PERIODS_PER_DAY.'  schedules for this day'
            );
    });

    it('does not error when day off exclusions exactly equal the maximum', function (): void {
        $user = User::factory()->create();

        $schedules = [];
        for ($i = 0; $i < UserSchedule::MAX_NUMBER_OF_ACTIVE_DAY_OFF_EXCLUSIONS; $i++) {
            $schedules[] = [
                'type'          => UserScheduleRecordType::DAY_OFF->value,
                'day_off_date'  => now()->addDays($i + 1)->toDateString(),
                'day_of_week'   => 1,
                'start_time'    => '00:00',
                'end_time'      => '23:59',
            ];
        }

        $errors = new CheckUserScheduleOverlap($schedules, [], $user->getKey())->verifyOverlappingErrors();

        expect($errors)->toBeArray()->toBeEmpty();
    });

    it('returns no errors when schedules are within limits and non-overlapping', function (): void {
        $user = User::factory()->create();

        $schedules = [
            [
                'day_of_week' => 2,
                'start_time'  => '09:00',
                'end_time'    => '10:00',
                'type'        => UserScheduleRecordType::WORKING_DAY->value,
            ],
            [
                'day_of_week' => 2,
                'start_time'  => '10:00',
                'end_time'    => '11:00',
                'type'        => UserScheduleRecordType::WORKING_DAY->value,
            ],
            [
                'type'          => UserScheduleRecordType::DAY_OFF->value,
                'day_off_date'  => now()->addWeek()->toDateString(),
                'day_of_week'   => 3,
                'start_time'    => '00:00',
                'end_time'      => '23:59',
            ],
        ];

        $errors = new CheckUserScheduleOverlap($schedules, [], $user->getKey())->verifyOverlappingErrors();

        expect($errors)->toBeArray()->toBeEmpty();
    });

    it('detects an overlap among the maximum number of same-day schedules', function (): void {
        $user = User::factory()->create();

        $schedules = [
            [
                'day_of_week' => 4,
                'start_time'  => '08:00',
                'end_time'    => '09:00',
                'type'        => UserScheduleRecordType::WORKING_DAY->value,
            ],
            [
                'day_of_week' => 4,
                'start_time'  => '09:00',
                'end_time'    => '10:00',
                'type'        => UserScheduleRecordType::WORKING_DAY->value,
            ],
            [
                'day_of_week' => 4,
                'start_time'  => '10:00',
                'end_time'    => '11:00',
                'type'        => UserScheduleRecordType::WORKING_DAY->value,
            ],
            [
                'day_of_week' => 4,
                'start_time'  => '10:30',
                'end_time'    => '11:30',
                'type'        => UserScheduleRecordType::WORKING_DAY->value,
            ],
        ];

        expect($schedules)->toHaveCount(UserSchedule::MAX_NUMBER_OF_SCHEDULES_PERIODS_PER_DAY);

        $errors = new CheckUserScheduleOverlap($schedules, [], $user->getKey())->verifyOverlappingErrors();

        $flattened = [];
        foreach ($errors as $pair) {
            foreach ($pair as $key => $message) {
                $flattened[$key] = $message;
            }
        }

        expect($flattened)->toContain('This time slot overlaps with another schedule on the same day.');
    });

    it('merges an existing overlapping working-day schedule from the database', function (): void {
        $user = User::factory()->create();

        UserSchedule::query()->create([
            'user_id'     => $user->getKey(),
            'day_of_week' => 5,
            'start_time'  => '09:00',
            'end_time'    => '10:00',
            'type'        => UserScheduleRecordType::WORKING_DAY->value,
        ]);

        $schedules = [
            [
                'day_of_week' => 5,
                'start_time'  => '09:30',
                'end_time'    => '10:30',
                'type'        => UserScheduleRecordType::WORKING_DAY->value,
            ],
        ];

        $errors = new CheckUserScheduleOverlap($schedules, [], $user->getKey())->verifyOverlappingErrors();

        $flattened = [];
        foreach ($errors as $pair) {
            foreach ($pair as $key => $message) {
                $flattened[$key] = $message;
            }
        }

        expect($flattened)->toContain('This time slot overlaps with another schedule on the same day.');
    });

    it('merges existing active day-off exclusions from the database into the count', function (): void {
        $user = User::factory()->create();

        for ($i = 0; $i < UserSchedule::MAX_NUMBER_OF_ACTIVE_DAY_OFF_EXCLUSIONS - 1; $i++) {
            UserSchedule::query()->create([
                'user_id'      => $user->getKey(),
                'day_of_week'  => 1,
                'start_time'   => '00:00',
                'end_time'     => '23:59',
                'type'         => UserScheduleRecordType::DAY_OFF->value,
                'day_off_date' => now()->addDays($i + 1)->toDateString(),
            ]);
        }

        $schedules = [
            [
                'type'          => UserScheduleRecordType::DAY_OFF->value,
                'day_off_date'  => now()->addDays(60)->toDateString(),
                'day_of_week'   => 1,
                'start_time'    => '00:00',
                'end_time'      => '23:59',
            ],
            [
                'type'          => UserScheduleRecordType::DAY_OFF->value,
                'day_off_date'  => now()->addDays(61)->toDateString(),
                'day_of_week'   => 1,
                'start_time'    => '00:00',
                'end_time'      => '23:59',
            ],
        ];

        $errors = new CheckUserScheduleOverlap($schedules, [], $user->getKey())->verifyOverlappingErrors();

        $flattened = [];
        foreach ($errors as $pair) {
            foreach ($pair as $key => $message) {
                $flattened[$key] = $message;
            }
        }

        expect($flattened)->toHaveKey('schedules.7.max_schedules_per_day');
    });

    it('excludes a past existing day-off exclusion from the database when counting', function (): void {
        $user = User::factory()->create();

        UserSchedule::query()->create([
            'user_id'      => $user->getKey(),
            'day_of_week'  => 1,
            'start_time'   => '00:00',
            'end_time'     => '23:59',
            'type'         => UserScheduleRecordType::DAY_OFF->value,
            'day_off_date' => now()->subDays(5)->toDateString(),
        ]);

        $schedules = [];
        for ($i = 0; $i < UserSchedule::MAX_NUMBER_OF_ACTIVE_DAY_OFF_EXCLUSIONS; $i++) {
            $schedules[] = [
                'type'          => UserScheduleRecordType::DAY_OFF->value,
                'day_off_date'  => now()->addDays($i + 1)->toDateString(),
                'day_of_week'   => 1,
                'start_time'    => '00:00',
                'end_time'      => '23:59',
            ];
        }

        $errors = new CheckUserScheduleOverlap($schedules, [], $user->getKey())->verifyOverlappingErrors();

        expect($errors)->toBeArray()->toBeEmpty();
    });
});
