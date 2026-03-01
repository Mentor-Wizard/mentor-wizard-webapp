<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\UserSchedule;
use App\Policies\UserSchedulePolicy;
use Database\Seeders\RoleSeeder;

mutates(UserSchedulePolicy::class);

describe('UserSchedulePolicy', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        $this->user = User::factory()->create();
        $this->policy = new UserSchedulePolicy;
    });

    it('allows upsert when no schedules provided', function (): void {
        $result = $this->policy->upsert($this->user, collect([]), []);

        expect($result)->toBeTrue();
    });

    it('allows upsert when only creating new schedules', function (): void {
        $scheduleBatch = collect([
            ['day_of_week' => 1, 'start_time' => '09:00', 'end_time' => '17:00'],
            ['day_of_week' => 2, 'start_time' => '09:00', 'end_time' => '17:00'],
        ]);

        $result = $this->policy->upsert($this->user, $scheduleBatch, []);

        expect($result)->toBeTrue();
    });

    it('allows upsert when updating own schedules', function (): void {
        $schedule1 = UserSchedule::factory()->create(['user_id' => $this->user->id]);
        $schedule2 = UserSchedule::factory()->create(['user_id' => $this->user->id]);

        $scheduleBatch = collect([
            ['id' => $schedule1->id, 'day_of_week' => 1, 'start_time' => '09:00', 'end_time' => '17:00'],
            ['id' => $schedule2->id, 'day_of_week' => 2, 'start_time' => '09:00', 'end_time' => '17:00'],
        ]);

        $result = $this->policy->upsert($this->user, $scheduleBatch, []);

        expect($result)->toBeTrue();
    });

    it('allows deleting own schedules', function (): void {
        $schedule1 = UserSchedule::factory()->create(['user_id' => $this->user->id]);
        $schedule2 = UserSchedule::factory()->create(['user_id' => $this->user->id]);

        $result = $this->policy->upsert($this->user, collect([]), [$schedule1->id, $schedule2->id]);

        expect($result)->toBeTrue();
    });

    it('denies upsert when trying to update another users schedule', function (): void {
        $otherUser = User::factory()->create();
        $otherSchedule = UserSchedule::factory()->create(['user_id' => $otherUser->id]);

        $scheduleBatch = collect([
            ['id' => $otherSchedule->id, 'day_of_week' => 1, 'start_time' => '09:00', 'end_time' => '17:00'],
        ]);

        $result = $this->policy->upsert($this->user, $scheduleBatch, []);

        expect($result)->toBeFalse();
    });

    it('denies upsert when trying to delete another users schedule', function (): void {
        $otherUser = User::factory()->create();
        $otherSchedule = UserSchedule::factory()->create(['user_id' => $otherUser->id]);

        $result = $this->policy->upsert($this->user, collect([]), [$otherSchedule->id]);

        expect($result)->toBeFalse();
    });

    it('pass upsert, in case no user schedules are set', function (): void {
        $otherUser = User::factory()->create();
        $otherSchedule = UserSchedule::factory()->create(['user_id' => $otherUser->id]);

        $result = $this->policy->upsert($this->user, collect([]), []);

        expect($result)->toBeTrue();
    });

    it('allows mixed create and update of own schedules', function (): void {
        $existingSchedule = UserSchedule::factory()->create(['user_id' => $this->user->id]);

        $scheduleBatch = collect([
            ['id' => $existingSchedule->id, 'day_of_week' => 1, 'start_time' => '09:00', 'end_time' => '17:00'],
            ['day_of_week' => 2, 'start_time' => '09:00', 'end_time' => '17:00'],
        ]);

        $result = $this->policy->upsert($this->user, $scheduleBatch, []);

        expect($result)->toBeTrue();
    });

    it('allows update and delete of own schedules simultaneously', function (): void {
        $schedule1 = UserSchedule::factory()->create(['user_id' => $this->user->id]);
        $schedule2 = UserSchedule::factory()->create(['user_id' => $this->user->id]);

        $scheduleBatch = collect([
            ['id' => $schedule1->id, 'day_of_week' => 1, 'start_time' => '09:00', 'end_time' => '17:00'],
        ]);

        $result = $this->policy->upsert($this->user, $scheduleBatch, [$schedule2->id]);

        expect($result)->toBeTrue();
    });

    it('denies when update list contains others schedule', function (): void {
        $ownSchedule = UserSchedule::factory()->create(['user_id' => $this->user->id]);
        $otherUser = User::factory()->create();
        $otherSchedule = UserSchedule::factory()->create(['user_id' => $otherUser->id]);

        $scheduleBatch = collect([
            ['id' => $ownSchedule->id, 'day_of_week' => 1, 'start_time' => '09:00', 'end_time' => '17:00'],
            ['id' => $otherSchedule->id, 'day_of_week' => 2, 'start_time' => '09:00', 'end_time' => '17:00'],
        ]);

        $result = $this->policy->upsert($this->user, $scheduleBatch, []);

        expect($result)->toBeFalse();
    });

    it('denies when delete list contains others schedule', function (): void {
        $ownSchedule = UserSchedule::factory()->create(['user_id' => $this->user->id]);
        $otherUser = User::factory()->create();
        $otherSchedule = UserSchedule::factory()->create(['user_id' => $otherUser->id]);

        $result = $this->policy->upsert($this->user, collect([]), [$ownSchedule->id, $otherSchedule->id]);

        expect($result)->toBeFalse();
    });

    it('filters out null values from id pluck', function (): void {
        $scheduleBatch = collect([
            ['id' => null, 'day_of_week' => 1],
            ['day_of_week' => 2],
            ['id'          => '', 'day_of_week' => 3],
        ]);

        $result = $this->policy->upsert($this->user, $scheduleBatch, []);

        expect($result)->toBeTrue();
    });

    it('handles empty array correctly', function (): void {
        $result = $this->policy->upsert($this->user, collect([]), []);

        expect($result)->toBeTrue();
    });

    it('correctly identifies schedules not owned by user', function (): void {
        $otherUser = User::factory()->create();
        $schedule1 = UserSchedule::factory()->create(['user_id' => $otherUser->id]);
        $schedule2 = UserSchedule::factory()->create(['user_id' => $otherUser->id]);

        $scheduleBatch = collect([
            ['id' => $schedule1->id, 'day_of_week' => 1],
            ['id' => $schedule2->id, 'day_of_week' => 2],
        ]);

        $result = $this->policy->upsert($this->user, $scheduleBatch, []);

        expect($result)->toBeFalse();
    });
});
