<?php

declare(strict_types=1);

use App\Enums\UserScheduleRecordType;
use App\Models\User;
use App\Models\UserSchedule;
use App\Services\Calendar\ExcludeUserScheduleSchemeService;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Date;

mutates(ExcludeUserScheduleSchemeService::class);

describe('ExcludeUserScheduleSchemeService', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
    });

    it('returns empty array when user has no schedule', function (): void {
        /** @var User $user */
        $user = User::factory()->create();

        $eventsSlots = [
            ['start' => Date::now(), 'end' => Date::now()->addHours(2)],
        ];

        $result = new ExcludeUserScheduleSchemeService($user, $eventsSlots, 'Europe/Kyiv');
        $slots = $result->getAvailableSlots();

        expect($slots)->toBeArray()->toBeEmpty();
    });

    it('filters slots to only include working hours', function (): void {
        $tz = 'Europe/Kyiv';
        Date::setTestNow(Date::create(2025, 1, 6, 8, 0, 0, $tz)); // Monday 8 AM

        /** @var User $user */
        $user = User::factory()->create();

        // Create schedule: Monday 9:00-17:00
        UserSchedule::query()->create([
            'user_id'     => $user->getKey(),
            'day_of_week' => 1, // Monday
            'start_time'  => '09:00:00',
            'end_time'    => '17:00:00',
            'type'        => UserScheduleRecordType::ALL_WORKING_DAYS,
            'timezone'    => $tz,
        ]);

        // Event slot from 8:00 to 18:00 (spans beyond working hours)
        $eventsSlots = [
            [
                'start' => Date::create(2025, 1, 6, 8, 0, 0, $tz),
                'end'   => Date::create(2025, 1, 6, 18, 0, 0, $tz),
            ],
        ];

        $result = new ExcludeUserScheduleSchemeService($user, $eventsSlots, $tz);
        $slots = $result->getAvailableSlots();

        expect($slots)->toBeArray()->toHaveCount(1);

        // Should be trimmed to working hours: 9:00-17:00
        expect($slots[0]['start']->format('H:i'))->toBe('09:00')
            ->and($slots[0]['end']->format('H:i'))->toBe('17:00');
    });

    it('handles multiple working day schedules for same day', function (): void {
        $tz = 'Europe/Kyiv';
        Date::setTestNow(Date::create(2025, 1, 6, 8, 0, 0, $tz)); // Monday

        /** @var User $user */
        $user = User::factory()->create();

        // Create two schedules for Monday: 9:00-12:00 and 14:00-17:00
        UserSchedule::query()->create([
            'user_id'     => $user->getKey(),
            'day_of_week' => 1,
            'start_time'  => '09:00:00',
            'end_time'    => '12:00:00',
            'type'        => UserScheduleRecordType::ALL_WORKING_DAYS,
            'timezone'    => $tz,
        ]);

        UserSchedule::query()->create([
            'user_id'     => $user->getKey(),
            'day_of_week' => 1,
            'start_time'  => '14:00:00',
            'end_time'    => '17:00:00',
            'type'        => UserScheduleRecordType::ALL_WORKING_DAYS,
            'timezone'    => $tz,
        ]);

        // Event slot all day
        $eventsSlots = [
            [
                'start' => Date::create(2025, 1, 6, 8, 0, 0, $tz),
                'end'   => Date::create(2025, 1, 6, 18, 0, 0, $tz),
            ],
        ];

        $result = new ExcludeUserScheduleSchemeService($user, $eventsSlots, $tz);
        $slots = $result->getAvailableSlots();

        expect($slots)->toBeArray()->toHaveCount(2);

        // First slot: 9:00-12:00
        expect($slots[0]['start']->format('H:i'))->toBe('09:00')
            ->and($slots[0]['end']->format('H:i'))->toBe('12:00');

        // Second slot: 14:00-17:00
        expect($slots[1]['start']->format('H:i'))->toBe('14:00')
            ->and($slots[1]['end']->format('H:i'))->toBe('17:00');
    });

    it('excludes slots on days without working schedule', function (): void {
        $tz = 'Europe/Kyiv';
        Date::setTestNow(Date::create(2025, 1, 6, 8, 0, 0, $tz)); // Monday

        /** @var User $user */
        $user = User::factory()->create();

        // Create schedule only for Monday
        UserSchedule::query()->create([
            'user_id'     => $user->getKey(),
            'day_of_week' => 1, // Monday
            'start_time'  => '09:00:00',
            'end_time'    => '17:00:00',
            'type'        => UserScheduleRecordType::ALL_WORKING_DAYS,
            'timezone'    => $tz,
        ]);

        // Event slots on Monday and Tuesday
        $eventsSlots = [
            [
                'start' => Date::create(2025, 1, 6, 10, 0, 0, $tz), // Monday
                'end'   => Date::create(2025, 1, 6, 16, 0, 0, $tz),
            ],
            [
                'start' => Date::create(2025, 1, 7, 10, 0, 0, $tz), // Tuesday (no schedule)
                'end'   => Date::create(2025, 1, 7, 16, 0, 0, $tz),
            ],
        ];

        $result = new ExcludeUserScheduleSchemeService($user, $eventsSlots, $tz);
        $slots = $result->getAvailableSlots();

        // Only Monday slot should be included
        expect($slots)->toBeArray()->toHaveCount(1);
        expect($slots[0]['start']->format('Y-m-d'))->toBe('2025-01-06');
    });

    it('excludes day off dates from available slots', function (): void {
        $tz = 'Europe/Kyiv';
        Date::setTestNow(Date::create(2025, 1, 6, 8, 0, 0, $tz)); // Monday

        /** @var User $user */
        $user = User::factory()->create();

        // Create working schedule for Monday
        UserSchedule::query()->create([
            'user_id'     => $user->getKey(),
            'day_of_week' => 1,
            'start_time'  => '09:00:00',
            'end_time'    => '17:00:00',
            'type'        => UserScheduleRecordType::ALL_WORKING_DAYS,
            'timezone'    => $tz,
        ]);

        // Create day off for Monday, January 13
        UserSchedule::query()->create([
            'user_id'      => $user->getKey(),
            'day_of_week'  => 1,
            'start_time'   => '00:00:00',
            'end_time'     => '23:59:59',
            'type'         => UserScheduleRecordType::DAY_OFF,
            'day_off_date' => Date::create(2025, 1, 13),
            'timezone'     => $tz,
        ]);

        // Event slots on two Mondays
        $eventsSlots = [
            [
                'start' => Date::create(2025, 1, 6, 10, 0, 0, $tz), // Monday (working day)
                'end'   => Date::create(2025, 1, 6, 16, 0, 0, $tz),
            ],
            [
                'start' => Date::create(2025, 1, 13, 10, 0, 0, $tz), // Monday (day off)
                'end'   => Date::create(2025, 1, 13, 16, 0, 0, $tz),
            ],
        ];

        $result = new ExcludeUserScheduleSchemeService($user, $eventsSlots, $tz);
        $slots = $result->getAvailableSlots();

        // Only the first Monday should be included
        expect($slots)->toBeArray()->toHaveCount(1);
        expect($slots[0]['start']->format('Y-m-d'))->toBe('2025-01-06');
    });

    it('handles timezone conversion correctly', function (): void {
        $scheduleTimezone = 'Europe/Kyiv';
        $chosenTimezone = 'America/New_York';

        Date::setTestNow(Date::create(2025, 1, 6, 12, 0, 0, $scheduleTimezone)); // Monday noon

        /** @var User $user */
        $user = User::factory()->create();

        // Schedule in Kyiv time: 9:00-17:00
        UserSchedule::query()->create([
            'user_id'     => $user->getKey(),
            'day_of_week' => 1,
            'start_time'  => '09:00:00',
            'end_time'    => '17:00:00',
            'type'        => UserScheduleRecordType::ALL_WORKING_DAYS,
            'timezone'    => $scheduleTimezone,
        ]);

        // Event slot in chosen timezone
        $eventsSlots = [
            [
                'start' => Date::create(2025, 1, 6, 8, 0, 0, $scheduleTimezone),
                'end'   => Date::create(2025, 1, 6, 18, 0, 0, $scheduleTimezone),
            ],
        ];

        $result = new ExcludeUserScheduleSchemeService($user, $eventsSlots, $chosenTimezone);
        $slots = $result->getAvailableSlots();

        expect($slots)->toBeArray()->toHaveCount(1);

        // Verify times are in chosen timezone
        expect($slots[0]['start']->timezone->getName())->toBe($chosenTimezone)
            ->and($slots[0]['end']->timezone->getName())->toBe($chosenTimezone);
    });

    it('handles multi-day event slots correctly', function (): void {
        $tz = 'Europe/Kyiv';
        Date::setTestNow(Date::create(2025, 1, 6, 8, 0, 0, $tz)); // Monday

        /** @var User $user */
        $user = User::factory()->create();

        // Create schedules for Monday and Tuesday
        UserSchedule::query()->create([
            'user_id'     => $user->getKey(),
            'day_of_week' => 1, // Monday
            'start_time'  => '09:00:00',
            'end_time'    => '17:00:00',
            'type'        => UserScheduleRecordType::ALL_WORKING_DAYS,
            'timezone'    => $tz,
        ]);

        UserSchedule::query()->create([
            'user_id'     => $user->getKey(),
            'day_of_week' => 2, // Tuesday
            'start_time'  => '10:00:00',
            'end_time'    => '16:00:00',
            'type'        => UserScheduleRecordType::ALL_WORKING_DAYS,
            'timezone'    => $tz,
        ]);

        // Event slot spanning two days
        $eventsSlots = [
            [
                'start' => Date::create(2025, 1, 6, 14, 0, 0, $tz), // Monday 14:00
                'end'   => Date::create(2025, 1, 7, 12, 0, 0, $tz), // Tuesday 12:00
            ],
        ];

        $result = new ExcludeUserScheduleSchemeService($user, $eventsSlots, $tz);
        $slots = $result->getAvailableSlots();

        // Should get slots for both days within working hours
        expect($slots)->toBeArray()->not()->toBeEmpty();

        // Find Monday slot
        $mondaySlots = array_filter($slots, fn($slot) => $slot['start']->format('Y-m-d') === '2025-01-06');
        expect($mondaySlots)->not()->toBeEmpty();

        // Find Tuesday slot
        $tuesdaySlots = array_filter($slots, fn($slot) => $slot['start']->format('Y-m-d') === '2025-01-07');
        expect($tuesdaySlots)->not()->toBeEmpty();
    });

    it('handles partial overlap with working hours', function (): void {
        $tz = 'Europe/Kyiv';
        Date::setTestNow(Date::create(2025, 1, 6, 8, 0, 0, $tz)); // Monday

        /** @var User $user */
        $user = User::factory()->create();

        // Create schedule: Monday 9:00-17:00
        UserSchedule::query()->create([
            'user_id'     => $user->getKey(),
            'day_of_week' => 1,
            'start_time'  => '09:00:00',
            'end_time'    => '17:00:00',
            'type'        => UserScheduleRecordType::ALL_WORKING_DAYS,
            'timezone'    => $tz,
        ]);

        // Event slot from 11:00 to 13:00 (fully within working hours)
        $eventsSlots = [
            [
                'start' => Date::create(2025, 1, 6, 11, 0, 0, $tz),
                'end'   => Date::create(2025, 1, 6, 13, 0, 0, $tz),
            ],
        ];

        $result = new ExcludeUserScheduleSchemeService($user, $eventsSlots, $tz);
        $slots = $result->getAvailableSlots();

        expect($slots)->toBeArray()->toHaveCount(1);

        // Slot should remain unchanged (11:00-13:00)
        expect($slots[0]['start']->format('H:i'))->toBe('11:00')
            ->and($slots[0]['end']->format('H:i'))->toBe('13:00');
    });

    it('handles event slot starting before and ending within working hours', function (): void {
        $tz = 'Europe/Kyiv';
        Date::setTestNow(Date::create(2025, 1, 6, 8, 0, 0, $tz)); // Monday

        /** @var User $user */
        $user = User::factory()->create();

        // Create schedule: Monday 9:00-17:00
        UserSchedule::query()->create([
            'user_id'     => $user->getKey(),
            'day_of_week' => 1,
            'start_time'  => '09:00:00',
            'end_time'    => '17:00:00',
            'type'        => UserScheduleRecordType::ALL_WORKING_DAYS,
            'timezone'    => $tz,
        ]);

        // Event slot from 8:00 to 12:00
        $eventsSlots = [
            [
                'start' => Date::create(2025, 1, 6, 8, 0, 0, $tz),
                'end'   => Date::create(2025, 1, 6, 12, 0, 0, $tz),
            ],
        ];

        $result = new ExcludeUserScheduleSchemeService($user, $eventsSlots, $tz);
        $slots = $result->getAvailableSlots();

        expect($slots)->toBeArray()->toHaveCount(1);

        // Should be trimmed to start at 9:00
        expect($slots[0]['start']->format('H:i'))->toBe('09:00')
            ->and($slots[0]['end']->format('H:i'))->toBe('12:00');
    });

    it('handles event slot starting within and ending after working hours', function (): void {
        $tz = 'Europe/Kyiv';
        Date::setTestNow(Date::create(2025, 1, 6, 8, 0, 0, $tz)); // Monday

        /** @var User $user */
        $user = User::factory()->create();

        // Create schedule: Monday 9:00-17:00
        UserSchedule::query()->create([
            'user_id'     => $user->getKey(),
            'day_of_week' => 1,
            'start_time'  => '09:00:00',
            'end_time'    => '17:00:00',
            'type'        => UserScheduleRecordType::ALL_WORKING_DAYS,
            'timezone'    => $tz,
        ]);

        // Event slot from 15:00 to 19:00
        $eventsSlots = [
            [
                'start' => Date::create(2025, 1, 6, 15, 0, 0, $tz),
                'end'   => Date::create(2025, 1, 6, 19, 0, 0, $tz),
            ],
        ];

        $result = new ExcludeUserScheduleSchemeService($user, $eventsSlots, $tz);
        $slots = $result->getAvailableSlots();

        expect($slots)->toBeArray()->toHaveCount(1);

        // Should be trimmed to end at 17:00
        expect($slots[0]['start']->format('H:i'))->toBe('15:00')
            ->and($slots[0]['end']->format('H:i'))->toBe('17:00');
    });
});
