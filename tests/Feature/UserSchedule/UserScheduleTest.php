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
    $this->user = User::factory()->create();
});

it('displays user schedule page for authenticated user', function (): void {
    actingAs($this->user);

    $response = $this->get(route('user-schedule.index'));

    $response->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
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

it('stores a new schedule successfully', function (): void {
    actingAs($this->user);

    $scheduleData = [
        'day_of_week' => 1,
        'start_time'  => '09:00',
        'end_time'    => '17:00',
        'type'        => UserScheduleRecordType::ALL_WORKING_DAYS->value,
        'timezone'    => 'UTC',
    ];

    $response = $this->post(route('user-schedule.store'), $scheduleData);

    $response->assertRedirect(route('user-schedule.index'));

    assertDatabaseHas('user_schedules', [
        'user_id'     => $this->user->getKey(),
        'day_of_week' => 1,
        'start_time'  => '09:00',
        'end_time'    => '17:00',
        'type'        => UserScheduleRecordType::ALL_WORKING_DAYS->value,
    ]);
});

it('stores a day off schedule with date', function (): void {
    actingAs($this->user);

    $scheduleData = [
        'day_of_week'  => 2,
        'start_time'   => '09:00',
        'end_time'     => '17:00',
        'type'         => UserScheduleRecordType::DAY_OFF->value,
        'day_off_date' => '2025-12-25',
        'timezone'     => 'UTC',
    ];

    $response = $this->post(route('user-schedule.store'), $scheduleData);

    $response->assertRedirect(route('user-schedule.index'));

    assertDatabaseHas('user_schedules', [
        'user_id'      => $this->user->getKey(),
        'type'         => UserScheduleRecordType::DAY_OFF->value,
        'day_off_date' => '2025-12-25',
    ]);
});

it('validates required fields', function (): void {
    actingAs($this->user);

    $response = $this->post(route('user-schedule.store'), []);

    $response->assertSessionHasErrors(['day_of_week', 'start_time', 'end_time', 'type', 'timezone']);
});

it('validates end time is after start time', function (): void {
    actingAs($this->user);

    $scheduleData = [
        'day_of_week' => 1,
        'start_time'  => '17:00',
        'end_time'    => '09:00',
        'type'        => UserScheduleRecordType::ALL_WORKING_DAYS->value,
        'timezone'    => 'UTC',
    ];

    $response = $this->post(route('user-schedule.store'), $scheduleData);

    $response->assertSessionHasErrors(['end_time']);
});

it('validates day_of_week is between 0 and 6', function (): void {
    actingAs($this->user);

    $scheduleData = [
        'day_of_week' => 7,
        'start_time'  => '09:00',
        'end_time'    => '17:00',
        'type'        => UserScheduleRecordType::ALL_WORKING_DAYS->value,
        'timezone'    => 'UTC',
    ];

    $response = $this->post(route('user-schedule.store'), $scheduleData);

    $response->assertSessionHasErrors(['day_of_week']);
});

it('validates day_off_date is required when type is Day off', function (): void {
    actingAs($this->user);

    $scheduleData = [
        'day_of_week' => 2,
        'start_time'  => '09:00',
        'end_time'    => '17:00',
        'type'        => UserScheduleRecordType::DAY_OFF->value,
        'timezone'    => 'UTC',
    ];

    $response = $this->post(route('user-schedule.store'), $scheduleData);

    $response->assertSessionHasErrors(['day_off_date']);
});

it('prevents overlapping schedules for the same day', function (): void {
    actingAs($this->user);

    // Create first schedule
    UserSchedule::factory()->create([
        'user_id'     => $this->user->getKey(),
        'day_of_week' => 1,
        'start_time'  => '09:00',
        'end_time'    => '12:00',
        'type'        => UserScheduleRecordType::ALL_WORKING_DAYS->value,
        'timezone'    => 'UTC',
    ]);

    // Try to create overlapping schedule
    $scheduleData = [
        'day_of_week' => 1,
        'start_time'  => '11:00',
        'end_time'    => '14:00',
        'type'        => UserScheduleRecordType::ALL_WORKING_DAYS->value,
        'timezone'    => 'UTC',
    ];

    $response = $this->post(route('user-schedule.store'), $scheduleData);

    $response->assertSessionHasErrors(['start_time']);
});

it('allows non-overlapping schedules for the same day', function (): void {
    actingAs($this->user);

    // Create first schedule
    UserSchedule::factory()->create([
        'user_id'     => $this->user->getKey(),
        'day_of_week' => 1,
        'start_time'  => '09:00',
        'end_time'    => '12:00',
        'type'        => UserScheduleRecordType::ALL_WORKING_DAYS->value,
        'timezone'    => 'UTC',
    ]);

    // Create non-overlapping schedule
    $scheduleData = [
        'day_of_week' => 1,
        'start_time'  => '13:00',
        'end_time'    => '17:00',
        'type'        => UserScheduleRecordType::ALL_WORKING_DAYS->value,
        'timezone'    => 'UTC',
    ];

    $response = $this->post(route('user-schedule.store'), $scheduleData);

    $response->assertRedirect(route('user-schedule.index'));
});

it('allows day-off schedules without overlap validation', function (): void {
    actingAs($this->user);

    // Create a working schedule
    UserSchedule::factory()->create([
        'user_id'     => $this->user->getKey(),
        'day_of_week' => 1,
        'start_time'  => '09:00',
        'end_time'    => '17:00',
        'type'        => UserScheduleRecordType::ALL_WORKING_DAYS->value,
        'timezone'    => 'UTC',
    ]);

    // Create a day-off schedule that would overlap (but should be allowed)
    $scheduleData = [
        'day_of_week'  => 1,
        'type'         => UserScheduleRecordType::DAY_OFF->value,
        'day_off_date' => '2025-12-25',
        'timezone'     => 'UTC',
    ];

    $response = $this->post(route('user-schedule.store'), $scheduleData);

    $response->assertRedirect(route('user-schedule.index'));

    // Verify day-off schedule has default times
    assertDatabaseHas('user_schedules', [
        'user_id'      => $this->user->getKey(),
        'type'         => UserScheduleRecordType::DAY_OFF->value,
        'day_off_date' => '2025-12-25',
        'start_time'   => '00:00:00',
        'end_time'     => '23:59:00',
    ]);
});

it('deletes own schedule successfully', function (): void {
    actingAs($this->user);

    $schedule = UserSchedule::factory()->create([
        'user_id'     => $this->user->getKey(),
        'day_of_week' => 1,
        'start_time'  => '09:00',
        'end_time'    => '17:00',
        'type'        => UserScheduleRecordType::ALL_WORKING_DAYS->value,
        'timezone'    => 'UTC',
    ]);

    $response = $this->delete(route('user-schedule.destroy', ['userSchedule' => $schedule->getKey()]));

    $response->assertRedirect(route('user-schedule.index'));

    assertDatabaseMissing('user_schedules', [
        'id' => $schedule->getKey(),
    ]);
});

it('prevents deleting another users schedule', function (): void {
    actingAs($this->user);

    $otherUser = User::factory()->create();
    $schedule = UserSchedule::factory()->create([
        'user_id'     => $otherUser->getKey(),
        'day_of_week' => 1,
        'start_time'  => '09:00',
        'end_time'    => '17:00',
        'type'        => UserScheduleRecordType::ALL_WORKING_DAYS->value,
        'timezone'    => 'UTC',
    ]);

    $response = $this->delete(route('user-schedule.destroy', ['userSchedule' => $schedule->getKey()]));

    $response->assertForbidden();

    assertDatabaseHas('user_schedules', [
        'id' => $schedule->getKey(),
    ]);
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
                'day_of_week' => 1,
                'start_time'  => '10:00',
                'end_time'    => '14:00',
                'type'        => UserScheduleRecordType::ODD_DAYS->value,
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
        'type'       => UserScheduleRecordType::ODD_DAYS->value,
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
                'day_of_week' => 1,
                'start_time'  => '10:00',
                'end_time'    => '13:00',
                'type'        => UserScheduleRecordType::ODD_DAYS->value,
                'timezone'    => 'UTC',
            ],
            // Create new
            [
                'day_of_week' => 2,
                'start_time'  => '14:00',
                'end_time'    => '18:00',
                'type'        => UserScheduleRecordType::ODD_DAYS->value,
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
                'type'        => UserScheduleRecordType::ODD_DAYS->value,
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
