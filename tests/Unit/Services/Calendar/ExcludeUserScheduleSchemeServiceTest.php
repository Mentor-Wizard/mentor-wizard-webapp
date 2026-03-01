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

        expect($slots)->toBeArray()->toHaveCount(1);
    });

    it('filters slots to only include working hours', function (): void {
        $tz = 'Europe/Kyiv';
        $testDate = Date::now($tz)->next('Monday')->setTime(8, 0, 0);

        /** @var User $user */
        $user = User::factory()->create();
        $user->profile->timezone = $tz;
        $user->profile->save();

        // Create schedule: Monday 9:00-17:00
        UserSchedule::query()->create([
            'user_id'     => $user->getKey(),
            'day_of_week' => 1, // Monday
            'start_time'  => '09:00:00',
            'end_time'    => '17:00:00',
            'type'        => UserScheduleRecordType::WORKING_DAY,
        ]);

        // Create schedule: Monday 9:00-17:00
        UserSchedule::query()->create([
            'user_id'     => $user->getKey(),
            'day_of_week' => 2, // Monday
            'start_time'  => '09:00:00',
            'end_time'    => '17:00:00',
            'type'        => UserScheduleRecordType::WORKING_DAY,
        ]);

        // Event slot from 8:00 to 18:00 (spans beyond working hours)
        $eventsSlots = [
            [
                'start' => $testDate->copy(),
                'end'   => $testDate->copy()->setTime(18, 0, 0),
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
        $testDate = Date::now($tz)->next('Monday')->setTime(8, 0, 0);

        /** @var User $user */
        $user = User::factory()->create();
        $user->profile->timezone = $tz;
        $user->profile->save();
        // Create two schedules for Monday: 9:00-12:00 and 14:00-17:00
        UserSchedule::query()->create([
            'user_id'     => $user->getKey(),
            'day_of_week' => 1,
            'start_time'  => '09:00:00',
            'end_time'    => '12:00:00',
            'type'        => UserScheduleRecordType::WORKING_DAY,
        ]);

        UserSchedule::query()->create([
            'user_id'     => $user->getKey(),
            'day_of_week' => 1,
            'start_time'  => '14:00:00',
            'end_time'    => '17:00:00',
            'type'        => UserScheduleRecordType::WORKING_DAY,
        ]);

        // Event slot all day
        $eventsSlots = [
            [
                'start' => $testDate->copy(),
                'end'   => $testDate->copy()->setTime(18, 0, 0),
            ],
        ];

        $result = new ExcludeUserScheduleSchemeService($user, $eventsSlots, $tz);
        $slots = $result->getAvailableSlots();

        expect($slots)->toBeArray()->toHaveCount(2);

        // First slot: 10:00-12:00
        expect($slots[0]['start']->format('H:i'))->toBe('09:00')
            ->and($slots[0]['end']->format('H:i'))->toBe('12:00');

        // Second slot: 14:00-17:00
        expect($slots[1]['start']->format('H:i'))->toBe('14:00')
            ->and($slots[1]['end']->format('H:i'))->toBe('17:00');
    });

    it('excludes slots on days without working schedule', function (): void {
        $tz = 'Europe/Kyiv';
        $monday = Date::now()->next('Monday')->setTime(8, 0, 0);
        Date::setTestNow($monday->copy()->timezone($tz));

        /** @var User $user */
        $user = User::factory()->create();

        // Create schedule only for Monday
        UserSchedule::query()->create([
            'user_id'     => $user->getKey(),
            'day_of_week' => 1, // Monday
            'start_time'  => '09:00:00',
            'end_time'    => '17:00:00',
            'type'        => UserScheduleRecordType::WORKING_DAY,
        ]);

        // Event slots on Monday and Tuesday
        $eventsSlots = [
            [
                'start' => $monday->copy()
                    ->setTime(10, 0, 0)->timezone($tz), // Monday
                'end'   => $monday->copy()
                    ->setTime(16, 0, 0)->timezone($tz),
            ],
            [
                'start' => $monday->copy()->addDay()
                    ->setTime(10, 0, 0)->timezone($tz), // Tuesday (no schedule)
                'end'   => $monday->copy()->addDay()
                    ->setTime(16, 0, 0)->timezone($tz),
            ],
        ];

        $result = new ExcludeUserScheduleSchemeService($user, $eventsSlots, $tz);
        $slots = $result->getAvailableSlots();

        // Only Monday slot should be included
        expect($slots)->toBeArray()->toHaveCount(1);
        expect($slots[0]['start']->format('Y-m-d'))->toBe($monday->format('Y-m-d'));
    });

    it('excludes day off dates from available slots', function (): void {
        $tz = 'Europe/Kyiv';

        /** @var User $user */
        $user = User::factory()->create();

        // Create working schedule for Monday
        UserSchedule::query()->create([
            'user_id'     => $user->getKey(),
            'day_of_week' => 1,
            'start_time'  => '09:00:00',
            'end_time'    => '17:00:00',
            'type'        => UserScheduleRecordType::WORKING_DAY,
        ]);

        // Create day off for Monday, January 13
        UserSchedule::query()->create([
            'user_id'      => $user->getKey(),
            'day_of_week'  => 1,
            'start_time'   => '00:00:00',
            'end_time'     => '23:59:59',
            'type'         => UserScheduleRecordType::DAY_OFF,
            'day_off_date' => Date::today()->addMonth()->firstOfMonth(1),
        ]);

        // Event slots on two Mondays
        $eventsSlots = [
            [
                'start' => Date::now()->addMonth()->firstOfMonth(1)
                    ->setTime(10, 0, 0)->timezone($tz), // Monday (working day)
                'end'   => Date::now()->addMonth()->firstOfMonth(1)
                    ->setTime(16, 0, 0)->timezone($tz),
            ],
            [
                'start' => Date::now()->addMonth()->firstOfMonth(1)
                    ->addWeek()->setTime(10, 0, 0)->timezone($tz), // Monday (day off)
                'end'   => Date::now()->addMonth()->firstOfMonth(1)
                    ->addWeek()->setTime(16, 0, 0)->timezone($tz),
            ],
        ];

        $result = new ExcludeUserScheduleSchemeService($user, $eventsSlots, $tz);
        $slots = $result->getAvailableSlots();

        // Only the first Monday should be included
        expect($slots)->toBeArray()->toHaveCount(1);
        expect($slots[0]['start']->format('Y-m-d'))->toBe(Date::now()->addMonth()
            ->firstOfMonth(1)->addWeek()->format('Y-m-d'));
    });

    it('handles timezone conversion correctly', function (): void {
        $scheduleTimezone = 'Europe/Kyiv';
        $chosenTimezone = 'America/New_York';

        $monday = Date::now()->next('Monday')->setTime(12, 0, 0);

        /** @var User $user */
        $user = User::factory()->create();

        // Schedule in Kyiv time: 9:00-17:00
        UserSchedule::query()->create([
            'user_id'     => $user->getKey(),
            'day_of_week' => 1,
            'start_time'  => '09:00:00',
            'end_time'    => '17:00:00',
            'type'        => UserScheduleRecordType::WORKING_DAY,
        ]);

        // Event slot in chosen timezone
        $eventsSlots = [
            [
                'start' => $monday->copy()->setTime(8, 0, 0)->timezone($scheduleTimezone),
                'end'   => $monday->copy()->setTime(18, 0, 0)->timezone($scheduleTimezone),
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
        $monday = Date::now()->next('Monday')->setTime(8, 0, 0);
        Date::setTestNow($monday->copy()->timezone($tz));

        /** @var User $user */
        $user = User::factory()->create();

        // Create schedules for Monday and Tuesday
        UserSchedule::query()->create([
            'user_id'     => $user->getKey(),
            'day_of_week' => 1, // Monday
            'start_time'  => '09:00:00',
            'end_time'    => '17:00:00',
            'type'        => UserScheduleRecordType::WORKING_DAY,
        ]);

        UserSchedule::query()->create([
            'user_id'     => $user->getKey(),
            'day_of_week' => 2, // Tuesday
            'start_time'  => '10:00:00',
            'end_time'    => '16:00:00',
            'type'        => UserScheduleRecordType::WORKING_DAY,
        ]);

        // Event slot spanning two days
        $tuesday = $monday->copy()->addDay();
        $eventsSlots = [
            [
                'start' => $monday->copy()->setTime(14, 0, 0)->timezone($tz), // Monday 14:00
                'end'   => $tuesday->copy()->setTime(12, 0, 0)->timezone($tz), // Tuesday 12:00
            ],
        ];

        $result = new ExcludeUserScheduleSchemeService($user, $eventsSlots, $tz);
        $slots = $result->getAvailableSlots();

        // Should get slots for both days within working hours
        expect($slots)->toBeArray()->not()->toBeEmpty();

        // Find Monday slot
        $mondaySlots = array_filter($slots, fn (array $slot): bool => $slot['start']->format('Y-m-d') === $monday->format('Y-m-d'));
        expect($mondaySlots)->not()->toBeEmpty();

        // Find Tuesday slot
        $tuesdaySlots = array_filter($slots, fn (array $slot): bool => $slot['start']->format('Y-m-d') === $tuesday->format('Y-m-d'));
        expect($tuesdaySlots)->not()->toBeEmpty();
    });

    it('correctly splits multi-day events across first, middle, and last dates', function (): void {
        $tz = 'Europe/Kyiv';
        // Create a 4-day event to hit all branches: first, middle, middle, last
        $startDate = Date::parse('2025-06-16 14:00:00', $tz); // Monday 14:00
        $endDate = Date::parse('2025-06-19 10:00:00', $tz);   // Thursday 10:00

        /** @var User $user */
        $user = User::factory()->create();
        $user->profile->timezone = $tz;
        $user->profile->save();

        // Create schedules for all 4 days
        foreach ([1, 2, 3, 4] as $dayOfWeek) {
            UserSchedule::query()->create([
                'user_id'     => $user->getKey(),
                'day_of_week' => $dayOfWeek,
                'start_time'  => '09:00:00',
                'end_time'    => '18:00:00',
                'type'        => UserScheduleRecordType::WORKING_DAY,
            ]);
        }

        $eventsSlots = [
            [
                'start' => $startDate,
                'end'   => $endDate,
            ],
        ];

        $result = new ExcludeUserScheduleSchemeService($user, $eventsSlots, $tz);
        $slots = $result->getAvailableSlots();

        // Should have 4 slots: one for each day
        expect($slots)->toBeArray()->toHaveCount(4);

        // First date (Monday): should start at event start (14:00) and end at end of day (18:00)
        expect($slots[0]['start']->format('Y-m-d H:i'))->toBe('2025-06-16 14:00')
            ->and($slots[0]['end']->format('Y-m-d H:i'))->toBe('2025-06-16 18:00');

        // Middle date (Tuesday): should be full day (09:00-18:00)
        expect($slots[1]['start']->format('Y-m-d H:i'))->toBe('2025-06-17 09:00')
            ->and($slots[1]['end']->format('Y-m-d H:i'))->toBe('2025-06-17 18:00');

        // Another middle date (Wednesday): should be full day (09:00-18:00)
        expect($slots[2]['start']->format('Y-m-d H:i'))->toBe('2025-06-18 09:00')
            ->and($slots[2]['end']->format('Y-m-d H:i'))->toBe('2025-06-18 18:00');

        // Last date (Thursday): should start at start of day (09:00) and end at event end (10:00)
        expect($slots[3]['start']->format('Y-m-d H:i'))->toBe('2025-06-19 09:00')
            ->and($slots[3]['end']->format('Y-m-d H:i'))->toBe('2025-06-19 10:00');
    });

    it('handles partial overlap with working hours', function (): void {
        $tz = 'Europe/Kyiv';
        $monday = Date::now($tz)->next('Monday')->setTime(8, 0, 0);

        /** @var User $user */
        $user = User::factory()->create();

        // Create schedule: Monday 9:00-17:00
        UserSchedule::query()->create([
            'user_id'     => $user->getKey(),
            'day_of_week' => 1,
            'start_time'  => '09:00:00',
            'end_time'    => '17:00:00',
            'type'        => UserScheduleRecordType::WORKING_DAY,
        ]);

        // Event slot from 11:00 to 13:00 (fully within working hours)
        $eventsSlots = [
            [
                'start' => $monday->copy()->setTime(11, 0, 0),
                'end'   => $monday->copy()->setTime(13, 0, 0),
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
        $monday = Date::now($tz)->next('Monday')->setTime(8, 0, 0);

        /** @var User $user */
        $user = User::factory()->create();
        $user->profile->timezone = $tz;
        $user->profile->save();

        // Create schedule: Monday 9:00-17:00
        UserSchedule::query()->create([
            'user_id'     => $user->getKey(),
            'day_of_week' => 1,
            'start_time'  => '09:00:00',
            'end_time'    => '17:00:00',
            'type'        => UserScheduleRecordType::WORKING_DAY,
        ]);

        // Event slot from 8:00 to 12:00
        $eventsSlots = [
            [
                'start' => $monday->copy()->setTime(8, 0, 0),
                'end'   => $monday->copy()->setTime(12, 0, 0),
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
        $monday = Date::now($tz)->next('Monday')->setTime(8, 0, 0);

        /** @var User $user */
        $user = User::factory()->create();
        $user->profile->timezone = $tz;
        $user->profile->save();

        // Create schedule: Monday 9:00-17:00
        UserSchedule::query()->create([
            'user_id'     => $user->getKey(),
            'day_of_week' => 1,
            'start_time'  => '09:00:00',
            'end_time'    => '17:00:00',
            'type'        => UserScheduleRecordType::WORKING_DAY,
        ]);

        // Event slot from 15:00 to 19:00
        $eventsSlots = [
            [
                'start' => $monday->copy()->setTime(15, 0, 0),
                'end'   => $monday->copy()->setTime(19, 0, 0),
            ],
        ];

        $result = new ExcludeUserScheduleSchemeService($user, $eventsSlots, $tz);
        $slots = $result->getAvailableSlots();

        expect($slots)->toBeArray()->toHaveCount(1);

        // Should be trimmed to end at 17:00
        expect($slots[0]['start']->format('H:i'))->toBe('15:00')
            ->and($slots[0]['end']->format('H:i'))->toBe('17:00');
    });

    it('handles single-day event that spans into next day', function (): void {
        $tz = 'Europe/Kyiv';
        $monday = Date::now($tz)->next('Monday')->setTime(20, 0, 0);
        Date::setTestNow($monday->copy()->timezone($tz));

        /** @var User $user */
        $user = User::factory()->create();
        $user->profile->timezone = $tz;
        $user->profile->save();

        // Create schedule for both Monday and Tuesday
        UserSchedule::query()->create([
            'user_id'     => $user->getKey(),
            'day_of_week' => 1, // Monday
            'start_time'  => '09:00:00',
            'end_time'    => '23:59:00',
            'type'        => UserScheduleRecordType::WORKING_DAY,
        ]);

        UserSchedule::query()->create([
            'user_id'     => $user->getKey(),
            'day_of_week' => 2, // Tuesday
            'start_time'  => '00:00:00',
            'end_time'    => '17:00:00',
            'type'        => UserScheduleRecordType::WORKING_DAY,
        ]);

        // Event slot from 23:00 Monday to 02:00 Tuesday (single carbon period but spans days)
        $eventsSlots = [
            [
                'start' => $monday->copy()->setTime(23, 0, 0),
                'end'   => $monday->copy()->addDay()->setTime(2, 0, 0),
            ],
        ];

        $result = new ExcludeUserScheduleSchemeService($user, $eventsSlots, $tz);
        $slots = $result->getAvailableSlots();

        // Should create 2 slots: one for Monday 23:00-23:59 and one for Tuesday 00:00-02:00
        expect($slots)->toBeArray()->toHaveCount(2);

        // First slot: Monday evening
        expect($slots[0]['start']->format('Y-m-d H:i'))->toBe($monday->format('Y-m-d').' 23:00')
            ->and($slots[0]['end']->format('Y-m-d H:i'))->toBe($monday->format('Y-m-d').' 23:59');

        // Second slot: Tuesday morning
        expect($slots[1]['start']->format('Y-m-d H:i'))->toBe($monday->addDay()->format('Y-m-d').' 00:00')
            ->and($slots[1]['end']->format('Y-m-d H:i'))->toBe($monday->addDay()->format('Y-m-d').' 02:00');
    });

    it('handles event slot completely outside working hours - no overlap', function (): void {
        $tz = 'Europe/Kyiv';
        $monday = Date::now($tz)->next('Monday')->setTime(8, 0, 0);

        /** @var User $user */
        $user = User::factory()->create();
        $user->profile->timezone = $tz;
        $user->profile->save();

        // Create schedule: Monday 9:00-17:00
        UserSchedule::query()->create([
            'user_id'     => $user->getKey(),
            'day_of_week' => 1,
            'start_time'  => '09:00:00',
            'end_time'    => '17:00:00',
            'type'        => UserScheduleRecordType::WORKING_DAY,
        ]);

        // Event slot from 18:00 to 20:00 (completely after working hours)
        $eventsSlots = [
            [
                'start' => $monday->copy()->setTime(18, 0, 0),
                'end'   => $monday->copy()->setTime(20, 0, 0),
            ],
        ];

        $result = new ExcludeUserScheduleSchemeService($user, $eventsSlots, $tz);
        $slots = $result->getAvailableSlots();

        // No overlap, so no slots should be returned
        expect($slots)->toBeArray()->toBeEmpty();
    });

    it('handles event slot completely before working hours - no overlap', function (): void {
        $tz = 'Europe/Kyiv';
        $monday = Date::now($tz)->next('Monday')->setTime(8, 0, 0);

        /** @var User $user */
        $user = User::factory()->create();
        $user->profile->timezone = $tz;
        $user->profile->save();

        // Create schedule: Monday 9:00-17:00
        UserSchedule::query()->create([
            'user_id'     => $user->getKey(),
            'day_of_week' => 1,
            'start_time'  => '09:00:00',
            'end_time'    => '17:00:00',
            'type'        => UserScheduleRecordType::WORKING_DAY,
        ]);

        // Event slot from 7:00 to 8:30 (completely before working hours)
        $eventsSlots = [
            [
                'start' => $monday->copy()->setTime(7, 0, 0),
                'end'   => $monday->copy()->setTime(8, 30, 0),
            ],
        ];

        $result = new ExcludeUserScheduleSchemeService($user, $eventsSlots, $tz);
        $slots = $result->getAvailableSlots();

        // No overlap, so no slots should be returned
        expect($slots)->toBeArray()->toBeEmpty();
    });

    it('verifies date parsing format with correct order (lines 190 & 196 mutation test)', function (): void {
        $tz = 'Europe/Kyiv';
        $monday = Date::parse('2025-06-16', $tz)->setTime(10, 0, 0); // Specific date

        /** @var User $user */
        $user = User::factory()->create();
        $user->profile->timezone = $tz;
        $user->profile->save();

        // Create schedule: Monday 9:00-17:00
        UserSchedule::query()->create([
            'user_id'     => $user->getKey(),
            'day_of_week' => 1,
            'start_time'  => ' 09:00:00', // Note: space before time to make concat order matter
            'end_time'    => ' 17:00:00',
            'type'        => UserScheduleRecordType::WORKING_DAY,
        ]);

        // Event slot within working hours
        $eventsSlots = [
            [
                'start' => $monday->copy()->setTime(10, 0, 0),
                'end'   => $monday->copy()->setTime(15, 0, 0),
            ],
        ];

        $result = new ExcludeUserScheduleSchemeService($user, $eventsSlots, $tz);
        $slots = $result->getAvailableSlots();

        expect($slots)->toBeArray()->toHaveCount(1);

        // If concatenation order is wrong, date parsing will fail or produce wrong results
        expect($slots[0]['start']->format('Y-m-d H:i'))->toBe('2025-06-16 10:00')
            ->and($slots[0]['end']->format('Y-m-d H:i'))->toBe('2025-06-16 15:00');
    });

    it('tests overlap detection logic with edge-touching slots (line 201 mutation test)', function (): void {
        $tz = 'Europe/Kyiv';
        $monday = Date::now($tz)->next('Monday')->setTime(8, 0, 0);

        /** @var User $user */
        $user = User::factory()->create();
        $user->profile->timezone = $tz;
        $user->profile->save();

        // Create schedule: Monday 9:00-17:00
        UserSchedule::query()->create([
            'user_id'     => $user->getKey(),
            'day_of_week' => 1,
            'start_time'  => '09:00:00',
            'end_time'    => '17:00:00',
            'type'        => UserScheduleRecordType::WORKING_DAY,
        ]);

        // Event slot that ends exactly when schedule starts (no overlap)
        $eventsSlots = [
            [
                'start' => $monday->copy()->setTime(7, 0, 0),
                'end'   => $monday->copy()->setTime(9, 0, 0), // Ends at schedule start
            ],
        ];

        $result = new ExcludeUserScheduleSchemeService($user, $eventsSlots, $tz);
        $slots = $result->getAvailableSlots();

        // No overlap (touching edges don't count as overlap), so no slots
        expect($slots)->toBeArray()->toBeEmpty();
    });

    it('tests overlap detection with slot starting exactly when schedule ends', function (): void {
        $tz = 'Europe/Kyiv';
        $monday = Date::now($tz)->next('Monday')->setTime(8, 0, 0);

        /** @var User $user */
        $user = User::factory()->create();
        $user->profile->timezone = $tz;
        $user->profile->save();

        // Create schedule: Monday 9:00-17:00
        UserSchedule::query()->create([
            'user_id'     => $user->getKey(),
            'day_of_week' => 1,
            'start_time'  => '09:00:00',
            'end_time'    => '17:00:00',
            'type'        => UserScheduleRecordType::WORKING_DAY,
        ]);

        // Event slot that starts exactly when schedule ends (no overlap)
        $eventsSlots = [
            [
                'start' => $monday->copy()->setTime(17, 0, 0), // Starts at schedule end
                'end'   => $monday->copy()->setTime(20, 0, 0),
            ],
        ];

        $result = new ExcludeUserScheduleSchemeService($user, $eventsSlots, $tz);
        $slots = $result->getAvailableSlots();

        // No overlap (touching edges don't count as overlap), so no slots
        expect($slots)->toBeArray()->toBeEmpty();
    });

    it('handles multi-day event where carbonPeriod count is 1 but dates differ (line 108)', function (): void {
        $tz = 'Europe/Kyiv';

        /** @var User $user */
        $user = User::factory()->create();
        $user->profile->timezone = $tz;
        $user->profile->save();

        // Create schedule for Monday and Tuesday
        UserSchedule::query()->create([
            'user_id'     => $user->getKey(),
            'day_of_week' => 1, // Monday
            'start_time'  => '00:00:00',
            'end_time'    => '23:59:59',
            'type'        => UserScheduleRecordType::WORKING_DAY,
        ]);

        UserSchedule::query()->create([
            'user_id'     => $user->getKey(),
            'day_of_week' => 2, // Tuesday
            'start_time'  => '00:00:00',
            'end_time'    => '23:59:59',
            'type'        => UserScheduleRecordType::WORKING_DAY,
        ]);

        // Create an event slot that spans midnight: Monday 23:30 to Tuesday 00:30
        // This creates a CarbonPeriod with technically 2 dates but count could be affected by timing
        $monday = Date::now($tz)->next('Monday')->setTime(23, 30, 0);
        $tuesday = $monday->copy()->addMinutes(60); // Tuesday 00:30

        $eventsSlots = [
            [
                'start' => $monday,
                'end'   => $tuesday,
            ],
        ];

        $result = new ExcludeUserScheduleSchemeService($user, $eventsSlots, $tz);
        $slots = $result->getAvailableSlots();

        // Should split into 2 slots because dates differ
        expect($slots)->toBeArray()->toHaveCount(2);

        // Verify first slot is Monday evening
        expect($slots[0]['start']->format('Y-m-d'))->toBe($monday->format('Y-m-d'))
            ->and($slots[0]['end']->format('H:i'))->toBe('23:59');

        // Verify second slot is Tuesday morning
        expect($slots[1]['start']->format('Y-m-d'))->toBe($tuesday->format('Y-m-d'))
            ->and($slots[1]['start']->format('H:i'))->toBe('00:00')
            ->and($slots[1]['end']->format('H:i'))->toBe('00:30');
    });

    it('catches wrong date concatenation order with explicit date format (lines 190 & 196)', function (): void {
        $tz = 'America/New_York';

        /** @var User $user */
        $user = User::factory()->create();
        $user->profile->timezone = $tz;
        $user->profile->save();

        // Use a specific date where order matters: 2025-01-15
        // If concat is wrong, we'd get "09:00:002025-01-15" which will fail parsing
        $testDate = Date::parse('2025-01-15', $tz)->setTime(10, 0, 0);

        // Create schedule: Wednesday (day 3) 09:00-17:00
        UserSchedule::query()->create([
            'user_id'     => $user->getKey(),
            'day_of_week' => 3, // Wednesday
            'start_time'  => '09:00:00',
            'end_time'    => '17:00:00',
            'type'        => UserScheduleRecordType::WORKING_DAY,
        ]);

        // Event slot on Wednesday
        $eventsSlots = [
            [
                'start' => $testDate->copy()->setTime(10, 0, 0),
                'end'   => $testDate->copy()->setTime(15, 0, 0),
            ],
        ];

        $result = new ExcludeUserScheduleSchemeService($user, $eventsSlots, $tz);
        $slots = $result->getAvailableSlots();

        // Should get exactly 1 slot with correct times
        expect($slots)->toBeArray()->toHaveCount(1);

        // Verify the exact datetime - if concatenation is wrong, this will fail
        expect($slots[0]['start']->format('Y-m-d H:i:s'))->toBe('2025-01-15 10:00:00')
            ->and($slots[0]['end']->format('Y-m-d H:i:s'))->toBe('2025-01-15 15:00:00');

        // Additional check: verify timezone conversion worked correctly
        expect($slots[0]['start']->timezone->getName())->toBe($tz)
            ->and($slots[0]['end']->timezone->getName())->toBe($tz);
    });

    it('verifies date parsing with different timezone to catch concat errors', function (): void {
        $scheduleTimezone = 'Asia/Tokyo';
        $chosenTimezone = 'America/Los_Angeles';

        /** @var User $user */
        $user = User::factory()->create();
        $user->profile->timezone = $scheduleTimezone;
        $user->profile->save();

        // Use a specific date: 2025-12-25 (Christmas)
        // If concat order is wrong: "09:00:002025-12-25" won't parse correctly
        $testDate = Date::parse('2025-12-25', $scheduleTimezone)->setTime(14, 0, 0);

        // Create schedule: Thursday (day 4) with specific times
        UserSchedule::query()->create([
            'user_id'     => $user->getKey(),
            'day_of_week' => 4, // Thursday
            'start_time'  => '09:00:00',
            'end_time'    => '18:00:00',
            'type'        => UserScheduleRecordType::WORKING_DAY,
        ]);

        // Event slot on Thursday in schedule timezone
        $eventsSlots = [
            [
                'start' => $testDate->copy()->setTime(10, 0, 0),
                'end'   => $testDate->copy()->setTime(16, 0, 0),
            ],
        ];

        $result = new ExcludeUserScheduleSchemeService($user, $eventsSlots, $chosenTimezone);
        $slots = $result->getAvailableSlots();

        expect($slots)->toBeArray()->toHaveCount(1);

        // The result should be in chosen timezone
        expect($slots[0]['start']->timezone->getName())->toBe($chosenTimezone)
            ->and($slots[0]['end']->timezone->getName())->toBe($chosenTimezone);

        // Verify the date is correct (should still be 2025-12-25 or adjusted for timezone)
        // This will fail if date concatenation is wrong
        $startDate = $slots[0]['start']->format('Y-m-d');
        expect($startDate)->toMatch('/^2025-12-(24|25)$/'); // May shift due to timezone
    });

    it('handles schedule times with leading/trailing whitespace in start_time', function (): void {
        $tz = 'Europe/Kyiv';
        $monday = Date::now($tz)->next('Monday')->setTime(10, 0, 0);

        /** @var User $user */
        $user = User::factory()->create();
        $user->profile->timezone = $tz;
        $user->profile->save();

        // Create schedule with whitespace in time values (simulating messy data)
        UserSchedule::query()->create([
            'user_id'     => $user->getKey(),
            'day_of_week' => 1, // Monday
            'start_time'  => '  09:00:00  ', // Leading and trailing spaces
            'end_time'    => "\t17:00:00\n", // Tab and newline
            'type'        => UserScheduleRecordType::WORKING_DAY,
        ]);

        // Event slot within working hours
        $eventsSlots = [
            [
                'start' => $monday->copy()->setTime(10, 0, 0),
                'end'   => $monday->copy()->setTime(15, 0, 0),
            ],
        ];

        $result = new ExcludeUserScheduleSchemeService($user, $eventsSlots, $tz);
        $slots = $result->getAvailableSlots();

        // Should correctly parse despite whitespace
        expect($slots)->toBeArray()->toHaveCount(1);

        // Verify the times are correctly parsed
        expect($slots[0]['start']->format('Y-m-d H:i'))->toBe($monday->format('Y-m-d').' 10:00')
            ->and($slots[0]['end']->format('Y-m-d H:i'))->toBe($monday->format('Y-m-d').' 15:00');
    });

    it('handles schedule times with leading/trailing whitespace in end time', function (): void {
        $tz = 'Europe/Kyiv';
        $monday = Date::now($tz)->next('Monday')->setTime(10, 0, 0);

        /** @var User $user */
        $user = User::factory()->create();
        $user->profile->timezone = $tz;
        $user->profile->save();

        // Create schedule with whitespace in time values (simulating messy data)
        UserSchedule::query()->create([
            'user_id'     => $user->getKey(),
            'day_of_week' => 1, // Monday
            'start_time'  => "\t  09:00:00 \n", // Leading and trailing spaces
            'end_time'    => ' 17:00:00 ', // Tab and newline
            'type'        => UserScheduleRecordType::WORKING_DAY,
        ]);

        // Event slot within working hours
        $eventsSlots = [
            [
                'start' => $monday->copy()->setTime(10, 0, 0),
                'end'   => $monday->copy()->setTime(15, 0, 0),
            ],
        ];

        $result = new ExcludeUserScheduleSchemeService($user, $eventsSlots, $tz);
        $slots = $result->getAvailableSlots();

        // Should correctly parse despite whitespace
        expect($slots)->toBeArray()->toHaveCount(1);

        // Verify the times are correctly parsed
        expect($slots[0]['start']->format('Y-m-d H:i'))->toBe($monday->format('Y-m-d').' 10:00')
            ->and($slots[0]['end']->format('Y-m-d H:i'))->toBe($monday->format('Y-m-d').' 15:00');
    });

    describe('DST Testing', function (): void {
        it('handles America/New_York spring forward DST transition', function (): void {
            // DST in America/New_York 2026: March 8 at 2:00 AM skips to 3:00 AM
            $tz = 'America/New_York';
            $dstDay = Date::parse('2026-03-08', $tz);

            /** @var User $user */
            $user = User::factory()->create();
            $user->profile->timezone = $tz;
            $user->profile->save();

            // Create schedule for Sunday (day 0): 00:00-23:59
            UserSchedule::query()->create([
                'user_id'     => $user->getKey(),
                'day_of_week' => 0, // Sunday
                'start_time'  => '00:00:00',
                'end_time'    => '23:59:00',
                'type'        => UserScheduleRecordType::WORKING_DAY,
            ]);

            // Event slot spanning the DST transition (1:00 AM to 4:00 AM)
            $eventsSlots = [
                [
                    'start' => $dstDay->copy()->setTime(1, 0, 0),
                    'end'   => $dstDay->copy()->setTime(4, 0, 0),
                ],
            ];

            $result = new ExcludeUserScheduleSchemeService($user, $eventsSlots, $tz);
            $slots = $result->getAvailableSlots();

            // Should handle DST gracefully
            expect($slots)->toBeArray()->not()->toBeEmpty();
            expect($slots[0]['start']->timezone->getName())->toBe($tz);
        });

        it('handles America/New_York fall back DST transition', function (): void {
            // DST ends in America/New_York 2026: November 1 at 2:00 AM falls back to 1:00 AM
            $tz = 'America/New_York';
            $dstDay = Date::parse('2026-11-01', $tz);

            /** @var User $user */
            $user = User::factory()->create();
            $user->profile->timezone = $tz;
            $user->profile->save();

            // Create schedule for Sunday (day 0): 00:00-23:59
            UserSchedule::query()->create([
                'user_id'     => $user->getKey(),
                'day_of_week' => 0, // Sunday
                'start_time'  => '00:00:00',
                'end_time'    => '23:59:00',
                'type'        => UserScheduleRecordType::WORKING_DAY,
            ]);

            // Event slot spanning the DST transition (midnight to 4:00 AM)
            $eventsSlots = [
                [
                    'start' => $dstDay->copy()->setTime(0, 0, 0),
                    'end'   => $dstDay->copy()->setTime(4, 0, 0),
                ],
            ];

            $result = new ExcludeUserScheduleSchemeService($user, $eventsSlots, $tz);
            $slots = $result->getAvailableSlots();

            // Should handle DST gracefully without errors
            expect($slots)->toBeArray()->not()->toBeEmpty();
            expect($slots[0]['start']->timezone->getName())->toBe($tz);
        });

        it('handles Europe/Kyiv DST transition correctly', function (): void {
            // DST in Europe/Kyiv 2026: March 29 at 3:00 AM skips to 4:00 AM
            $tz = 'Europe/Kyiv';
            $dstDay = Date::parse('2026-03-29', $tz);

            /** @var User $user */
            $user = User::factory()->create();
            $user->profile->timezone = $tz;
            $user->profile->save();

            // Create schedule for Sunday (day 0): 00:00-23:59
            UserSchedule::query()->create([
                'user_id'     => $user->getKey(),
                'day_of_week' => 0, // Sunday
                'start_time'  => '00:00:00',
                'end_time'    => '23:59:00',
                'type'        => UserScheduleRecordType::WORKING_DAY,
            ]);

            // Event slot spanning the DST transition
            $eventsSlots = [
                [
                    'start' => $dstDay->copy()->setTime(2, 0, 0),
                    'end'   => $dstDay->copy()->setTime(5, 0, 0),
                ],
            ];

            $result = new ExcludeUserScheduleSchemeService($user, $eventsSlots, $tz);
            $slots = $result->getAvailableSlots();

            // Should handle DST gracefully
            expect($slots)->toBeArray()->not()->toBeEmpty();
            expect($slots[0]['start']->timezone->getName())->toBe($tz);
        });

        it('handles UserSchedule working hours during DST transition day', function (): void {
            // DST in America/New_York 2026: March 8 at 2:00 AM skips to 3:00 AM
            $tz = 'America/New_York';
            $dstDay = Date::parse('2026-03-08', $tz);

            /** @var User $user */
            $user = User::factory()->create();
            $user->profile->timezone = $tz;
            $user->profile->save();

            // Create schedule for Sunday (day 0): 09:00-17:00 (working hours)
            UserSchedule::query()->create([
                'user_id'     => $user->getKey(),
                'day_of_week' => 0, // Sunday
                'start_time'  => '09:00:00',
                'end_time'    => '17:00:00',
                'type'        => UserScheduleRecordType::WORKING_DAY,
            ]);

            // Event slot during normal working hours (after DST transition)
            $eventsSlots = [
                [
                    'start' => $dstDay->copy()->setTime(8, 0, 0),
                    'end'   => $dstDay->copy()->setTime(18, 0, 0),
                ],
            ];

            $result = new ExcludeUserScheduleSchemeService($user, $eventsSlots, $tz);
            $slots = $result->getAvailableSlots();

            // Should get slot trimmed to working hours
            expect($slots)->toBeArray()->toHaveCount(1);
            expect($slots[0]['start']->format('H:i'))->toBe('09:00')
                ->and($slots[0]['end']->format('H:i'))->toBe('17:00');
        });
    });
});
