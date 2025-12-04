<?php

declare(strict_types=1);

use App\Enums\RoleEnum;
use App\Enums\UserScheduleRecordType;
use App\Models\User;
use App\Models\UserSchedule;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

beforeEach(function (): void {
    Role::create(['name' => RoleEnum::USER]);
    Role::create(['name' => RoleEnum::MENTOR]);
    $this->user = User::factory()->create();
    $this->user->assignRole(RoleEnum::MENTOR);
});

it('displays user schedule page for authenticated user', function (): void {
    actingAs($this->user);

    $response = $this->get(route('user-schedule.index'));

    $response->assertSuccessful()
        ->assertInertia(fn (Assert $page): Assert => $page
            ->component('UserSchedule/ListPage')
            ->has('schedules')
            ->has('scheduleTypes')
            ->has('timezone')
        );
});

it('redirects unauthenticated user from schedule page', function (): void {
    $response = $this->get(route('user-schedule.index'));

    $response->assertRedirect(route('login'));
});

// Batch Operation Tests
it('saves multiple schedules in one batch request', function (): void {
    $this->withoutMiddleware();
    actingAs($this->user);

    $batchData = [
        'schedules' => [
            [
                'day_of_week' => 1,
                'start_time'  => '09:00',
                'end_time'    => '12:00',
                'type'        => UserScheduleRecordType::ALL_WORKING_DAYS->value,
                'timezone'    => 'UTC',
            ],
            [
                'day_of_week' => 2,
                'start_time'  => '13:00',
                'end_time'    => '17:00',
                'type'        => UserScheduleRecordType::ALL_WORKING_DAYS->value,
                'timezone'    => 'UTC',
            ],
        ],
        'delete_ids' => [],
    ];

    $response = $this->post(route('user-schedule.batch'), $batchData);

    $response->assertRedirect(route('user-schedule.index'))
        ->assertSessionHas('success', 'Schedules saved successfully.');

    assertDatabaseHas('user_schedules', [
        'user_id'     => $this->user->getKey(),
        'day_of_week' => 1,
        'start_time'  => '09:00',
    ]);

    assertDatabaseHas('user_schedules', [
        'user_id'     => $this->user->getKey(),
        'day_of_week' => 2,
        'start_time'  => '13:00',
    ]);
});

it('updates existing schedules in batch request', function (): void {
    $this->withoutMiddleware();
    actingAs($this->user);

    $schedule = UserSchedule::factory()->create([
        'user_id'     => $this->user->getKey(),
        'day_of_week' => 1,
        'start_time'  => '09:00',
        'end_time'    => '12:00',
        'type'        => UserScheduleRecordType::ALL_WORKING_DAYS->value,
        'timezone'    => 'UTC',
    ]);

    $batchData = [
        'schedules' => [
            [
                'id'          => $schedule->getKey(),
                'user_id'     => $this->user->getKey(),
                'day_of_week' => 1,
                'start_time'  => '10:00',
                'end_time'    => '14:00',
                'type'        => UserScheduleRecordType::ALL_WORKING_DAYS->value,
                'timezone'    => 'UTC',
            ],
        ],
        'delete_ids' => [],
    ];

    $response = $this->post(route('user-schedule.batch'), $batchData);

    $response->assertRedirect(route('user-schedule.index'))
        ->assertSessionHas('success');

    assertDatabaseHas('user_schedules', [
        'id'         => $schedule->getKey(),
        'start_time' => '10:00',
        'end_time'   => '14:00',
        'type'       => UserScheduleRecordType::ALL_WORKING_DAYS->value,
    ]);
});

it('deletes schedules in batch request', function (): void {
    $this->withoutMiddleware();
    actingAs($this->user);

    $schedule1 = UserSchedule::factory()->create([
        'user_id'     => $this->user->getKey(),
        'day_of_week' => 1,
        'start_time'  => '09:00',
        'end_time'    => '12:00',
        'type'        => UserScheduleRecordType::ALL_WORKING_DAYS->value,
        'timezone'    => 'UTC',
    ]);

    $schedule2 = UserSchedule::factory()->create([
        'user_id'     => $this->user->getKey(),
        'day_of_week' => 2,
        'start_time'  => '09:00',
        'end_time'    => '12:00',
        'type'        => UserScheduleRecordType::ALL_WORKING_DAYS->value,
        'timezone'    => 'UTC',
    ]);

    $batchData = [
        'schedules'  => [],
        'delete_ids' => [$schedule1->getKey(), $schedule2->getKey()],
    ];

    $response = $this->post(route('user-schedule.batch'), $batchData);

    $response->assertRedirect(route('user-schedule.index'))
        ->assertSessionHas('success');

    assertDatabaseMissing('user_schedules', [
        'id' => $schedule1->getKey(),
    ]);

    assertDatabaseMissing('user_schedules', [
        'id' => $schedule2->getKey(),
    ]);
});

it('creates, updates, and deletes schedules in one batch request', function (): void {
    $this->withoutMiddleware();
    actingAs($this->user);

    $existingSchedule = UserSchedule::factory()->create([
        'user_id'     => $this->user->getKey(),
        'day_of_week' => 1,
        'start_time'  => '09:00',
        'end_time'    => '12:00',
        'type'        => UserScheduleRecordType::ALL_WORKING_DAYS->value,
        'timezone'    => 'UTC',
    ]);

    $scheduleToDelete = UserSchedule::factory()->create([
        'user_id'     => $this->user->getKey(),
        'day_of_week' => 3,
        'start_time'  => '09:00',
        'end_time'    => '12:00',
        'type'        => UserScheduleRecordType::ALL_WORKING_DAYS->value,
        'timezone'    => 'UTC',
    ]);

    $batchData = [
        'schedules' => [
            // Update existing
            [
                'id'          => $existingSchedule->getKey(),
                'user_id'     => $this->user->getKey(),
                'day_of_week' => 1,
                'start_time'  => '10:00',
                'end_time'    => '13:00',
                'type'        => UserScheduleRecordType::ALL_WORKING_DAYS->value,
                'timezone'    => 'UTC',
            ],
            // Create new
            [
                'day_of_week' => 2,
                'start_time'  => '14:00',
                'end_time'    => '18:00',
                'type'        => UserScheduleRecordType::ALL_WORKING_DAYS->value,
                'timezone'    => 'UTC',
            ],
        ],
        'delete_ids' => [$scheduleToDelete->getKey()],
    ];

    $response = $this->post(route('user-schedule.batch'), $batchData);

    $response->assertRedirect(route('user-schedule.index'))
        ->assertSessionHas('success');

    // Check update
    assertDatabaseHas('user_schedules', [
        'id'         => $existingSchedule->getKey(),
        'start_time' => '10:00',
    ]);

    // Check create
    assertDatabaseHas('user_schedules', [
        'user_id'     => $this->user->getKey(),
        'day_of_week' => 2,
        'start_time'  => '14:00',
    ]);

    // Check delete
    assertDatabaseMissing('user_schedules', [
        'id' => $scheduleToDelete->getKey(),
    ]);
});

it('validates overlapping schedules in batch request', function (): void {
    $this->withoutMiddleware();
    actingAs($this->user);

    $batchData = [
        'schedules' => [
            [
                'day_of_week' => 1,
                'start_time'  => '09:00',
                'end_time'    => '13:00',
                'type'        => UserScheduleRecordType::ALL_WORKING_DAYS->value,
                'timezone'    => 'UTC',
            ],
            [
                'day_of_week' => 1,
                'start_time'  => '12:00',
                'end_time'    => '16:00',
                'type'        => UserScheduleRecordType::ALL_WORKING_DAYS->value,
                'timezone'    => 'UTC',
            ],
        ],
        'delete_ids' => [],
    ];

    $response = $this->post(route('user-schedule.batch'), $batchData);

    $response->assertSessionHasErrors();
});

it('prevents batch deletion of another users schedules', function (): void {
    $this->withoutMiddleware();
    actingAs($this->user);

    $otherUser = User::factory()->create();
    $otherSchedule = UserSchedule::factory()->create([
        'user_id'     => $otherUser->getKey(),
        'day_of_week' => 1,
        'start_time'  => '09:00',
        'end_time'    => '12:00',
        'type'        => UserScheduleRecordType::ALL_WORKING_DAYS->value,
        'timezone'    => 'UTC',
    ]);

    $batchData = [
        'schedules'  => [],
        'delete_ids' => [$otherSchedule->getKey()],
    ];

    $response = $this->post(route('user-schedule.batch'), $batchData);

    $response->assertSessionHasErrors(['delete_ids']);

    assertDatabaseHas('user_schedules', [
        'id' => $otherSchedule->getKey(),
    ]);
});

it('prevents batch update of another users schedules', function (): void {
    $this->withoutMiddleware();
    actingAs($this->user);

    $otherUser = User::factory()->create();
    $otherSchedule = UserSchedule::factory()->create([
        'user_id'     => $otherUser->getKey(),
        'day_of_week' => 1,
        'start_time'  => '09:00',
        'end_time'    => '12:00',
        'type'        => UserScheduleRecordType::ALL_WORKING_DAYS->value,
        'timezone'    => 'UTC',
    ]);

    $batchData = [
        'schedules' => [
            [
                'id'          => $otherSchedule->getKey(),
                'day_of_week' => 1,
                'start_time'  => '10:00',
                'end_time'    => '14:00',
                'type'        => UserScheduleRecordType::ALL_WORKING_DAYS->value,
                'timezone'    => 'UTC',
            ],
        ],
        'delete_ids' => [],
    ];

    $response = $this->post(route('user-schedule.batch'), $batchData);

    $response->assertSessionHasErrors();

    assertDatabaseHas('user_schedules', [
        'id'         => $otherSchedule->getKey(),
        'start_time' => '09:00',
    ]);
});
