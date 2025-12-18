<?php

declare(strict_types=1);

use App\Enums\UserScheduleRecordType;
use App\Models\User;
use App\Models\UserSchedule;
use App\Services\UserSchedule\CheckUserScheduleOverlap;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
});

describe('CheckUserScheduleOverlap', function (): void {
    it('returns error when schedules per day exceed the maximum', function (): void {
        $user = User::factory()->create();

        $schedules = [];
        // Create 5 periods for Monday (1)
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

        // Flatten to keys for easy assertion
        $keys = [];
        foreach ($errors as $pair) {
            foreach ($pair as $key => $message) {
                $keys[] = $key;
            }
        }

        expect($keys)->toContain('schedules.1.max_schedules_per_day');
    });

    it('returns error when day off exclusions exceed the maximum', function (): void {
        $user = User::factory()->create();

        $schedules = [];
        // Create MAX + 1 day-off exclusions
        for ($i = 0; $i < UserSchedule::MAX_NUMBER_OF_ACTIVE_DAY_OFF_EXCLUSIONS + 1; $i++) {
            $schedules[] = [
                'type'          => UserScheduleRecordType::DAY_OFF->value,
                // any day_off_date in the future is fine
                'day_off_date'  => now()->addDays($i + 1)->toDateString(),
                'day_of_week'   => 1,
                'start_time'    => '00:00',
                'end_time'      => '23:59',
            ];
        }

        $errors = new CheckUserScheduleOverlap($schedules, [], $user->getKey())->verifyOverlappingErrors();

        $keys = [];
        foreach ($errors as $pair) {
            foreach ($pair as $key => $message) {
                $keys[] = $key;
            }
        }

        // 7 is the special index used in Vue component for exclusions bucket
        expect($keys)->toContain('schedules.7.max_schedules_per_day');
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

        expect($errors)->toBeArray()->toHaveCount(0);
    });
});
