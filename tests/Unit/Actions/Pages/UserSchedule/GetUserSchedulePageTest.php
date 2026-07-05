<?php

declare(strict_types=1);

use App\Actions\Pages\UserSchedule\UserSchedulePage;
use App\Enums\RoleEnum;
use App\Enums\UserScheduleRecordType;
use App\Models\User;
use App\Models\UserSchedule;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Date;
use Inertia\Response as InertiaResponse;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;

mutates(UserSchedulePage::class);

describe('UserSchedulePage', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        $this->user = User::factory()->create();
        $this->user->assignRole(Role::findByName(RoleEnum::MENTOR->value));
    });

    it('returns Inertia response with correct component name', function (): void {
        actingAs($this->user);

        $action = new UserSchedulePage;
        $response = $action->handle();

        expect($response)
            ->toBeInstanceOf(InertiaResponse::class);
    });

    it('returns empty schedules when user has no schedule records', function (): void {
        actingAs($this->user);

        $action = new UserSchedulePage;
        $response = $action->handle();

        $props = inertiaProps($response);

        expect($props)
            ->toHaveKeys(['schedules', 'scheduleTypes', 'timezone'])
            ->and($props['schedules'])->toBeArray()->toBeEmpty()
            ->and($props['timezone'])->toBe('UTC');
    });

    it('returns schedules ordered by day_of_week and start_time', function (): void {
        actingAs($this->user);

        // Create schedules in reverse order
        UserSchedule::factory()->create([
            'user_id'     => $this->user->id,
            'day_of_week' => 3, // Wednesday
            'start_time'  => '14:00:00',
            'type'        => UserScheduleRecordType::WORKING_DAY,
        ]);

        UserSchedule::factory()->create([
            'user_id'     => $this->user->id,
            'day_of_week' => 1, // Monday
            'start_time'  => '09:00:00',
            'type'        => UserScheduleRecordType::WORKING_DAY,
        ]);

        UserSchedule::factory()->create([
            'user_id'     => $this->user->id,
            'day_of_week' => 1, // Monday
            'start_time'  => '14:00:00',
            'type'        => UserScheduleRecordType::WORKING_DAY,
        ]);

        $action = new UserSchedulePage;
        $response = $action->handle();

        $props = inertiaProps($response);

        expect($props['schedules'])->toHaveCount(3);

        // First should be Monday 09:00
        expect($props['schedules'][0]['day_of_week'])->toBe(1)
            ->and($props['schedules'][0]['start_time'])->toBe('09:00:00');

        // Second should be Monday 14:00
        expect($props['schedules'][1]['day_of_week'])->toBe(1)
            ->and($props['schedules'][1]['start_time'])->toBe('14:00:00');

        // Third should be Wednesday 14:00
        expect($props['schedules'][2]['day_of_week'])->toBe(3)
            ->and($props['schedules'][2]['start_time'])->toBe('14:00:00');
    });

    it('includes schedule types collection in props', function (): void {
        actingAs($this->user);

        $action = new UserSchedulePage;
        $response = $action->handle();

        $props = inertiaProps($response);

        expect($props['scheduleTypes'])->toBeArray()->not()->toBeEmpty()->each->toHaveKeys(['value', 'label']);
    });

    it('returns new timezone when changed timezone for profile', function (): void {
        actingAs($this->user);

        $newTimezone = 'Europe/Kyiv';
        $this->user->profile->timezone = $newTimezone;
        $this->user->profile->save();

        UserSchedule::factory()->create([
            'user_id'  => $this->user->id,
        ]);

        $action = new UserSchedulePage;
        $response = $action->handle();

        $props = inertiaProps($response);

        expect($props['timezone'])->toBe($newTimezone);
    });

    it('returns only active schedule records', function (): void {
        actingAs($this->user);

        // Create active schedule
        UserSchedule::factory()->create([
            'user_id'     => $this->user->id,
            'day_of_week' => 1,
            'type'        => UserScheduleRecordType::WORKING_DAY,
        ]);

        $action = new UserSchedulePage;
        $response = $action->handle();

        $props = inertiaProps($response);

        expect($props['schedules'])->toHaveCount(1);
    });

    it('includes day_off_date formatted as Y-m-d when present', function (): void {
        actingAs($this->user);

        $dayOffDate = Date::now()->addWeek();

        UserSchedule::factory()->create([
            'user_id'      => $this->user->id,
            'type'         => UserScheduleRecordType::DAY_OFF,
            'day_off_date' => $dayOffDate,
        ]);

        $action = new UserSchedulePage;
        $response = $action->handle();

        $props = inertiaProps($response);

        expect($props['schedules'][0]['day_off_date'])
            ->toBe($dayOffDate->format('Y-m-d'));
    });

    it('includes null day_off_date when not present', function (): void {
        actingAs($this->user);

        UserSchedule::factory()->create([
            'user_id'      => $this->user->id,
            'type'         => UserScheduleRecordType::WORKING_DAY,
            'day_off_date' => null,
        ]);

        $action = new UserSchedulePage;
        $response = $action->handle();

        $props = inertiaProps($response);

        expect($props['schedules'][0]['day_off_date'])->toBeNull();
    });

    it('only returns schedules for authenticated user', function (): void {
        actingAs($this->user);

        $otherUser = User::factory()->create();

        UserSchedule::factory()->create([
            'user_id' => $this->user->id,
        ]);

        UserSchedule::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $action = new UserSchedulePage;
        $response = $action->handle();

        $props = inertiaProps($response);

        expect($props['schedules'])->toHaveCount(1)
            ->and($props['schedules'][0]['user_id'])->toBe($this->user->id);
    });
});
