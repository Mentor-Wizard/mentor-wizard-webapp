<?php

declare(strict_types=1);

use App\Enums\RoleEnum;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\UserSchedule\Enums\UserScheduleRecordType;
use Modules\UserSchedule\Models\UserSchedule;
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
        );
});

// Batch Operation Tests
it('saves multiple schedules in one batch request', function (): void {
    actingAs($this->user);

    $batchData = [
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

    $response = $this->post(route('user-schedule.batch'), $batchData);

    $response->assertRedirect(route('user-schedule.index'))
        ->assertSessionHas('success', 'Schedules were saved successfully.');

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
    actingAs($this->user);

    $schedule = UserSchedule::factory()->create([
        'user_id'     => $this->user->getKey(),
        'day_of_week' => 1,
        'start_time'  => '09:00',
        'end_time'    => '12:00',
        'type'        => UserScheduleRecordType::WORKING_DAY->value,
    ]);

    $batchData = [
        'schedules' => [
            [
                'id'          => $schedule->getKey(),
                'user_id'     => $this->user->getKey(),
                'day_of_week' => 1,
                'start_time'  => '10:00',
                'end_time'    => '14:00',
                'type'        => UserScheduleRecordType::WORKING_DAY->value,
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
        'type'       => UserScheduleRecordType::WORKING_DAY->value,
    ]);
});

it('deletes schedules in batch request', function (): void {
    actingAs($this->user);

    $schedule1 = UserSchedule::factory()->create([
        'user_id'     => $this->user->getKey(),
        'day_of_week' => 1,
        'start_time'  => '09:00',
        'end_time'    => '12:00',
        'type'        => UserScheduleRecordType::WORKING_DAY->value,
    ]);

    $schedule2 = UserSchedule::factory()->create([
        'user_id'     => $this->user->getKey(),
        'day_of_week' => 2,
        'start_time'  => '09:00',
        'end_time'    => '12:00',
        'type'        => UserScheduleRecordType::WORKING_DAY->value,
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
    actingAs($this->user);

    $existingSchedule = UserSchedule::factory()->create([
        'user_id'     => $this->user->getKey(),
        'day_of_week' => 1,
        'start_time'  => '09:00',
        'end_time'    => '12:00',
        'type'        => UserScheduleRecordType::WORKING_DAY->value,
    ]);

    $scheduleToDelete = UserSchedule::factory()->create([
        'user_id'     => $this->user->getKey(),
        'day_of_week' => 3,
        'start_time'  => '09:00',
        'end_time'    => '12:00',
        'type'        => UserScheduleRecordType::WORKING_DAY->value,

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
                'type'        => UserScheduleRecordType::WORKING_DAY->value,
            ],
            // Create new
            [
                'day_of_week' => 2,
                'start_time'  => '14:00',
                'end_time'    => '18:00',
                'type'        => UserScheduleRecordType::WORKING_DAY->value,
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

it('fails when exceeding maximum number of schedules per day', function (): void {
    actingAs($this->user);
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

    $response = $this->from(route('user-schedule.index'))
        ->post(route('user-schedule.batch'), [
            'schedules'  => $schedules,
            'delete_ids' => [],
        ]);

    $response->assertRedirect(route('user-schedule.index'))
        ->assertSessionHasErrors(['schedules.1.0.max_schedules_per_day']);
});

it('fails when exceeding maximum number of day-off exclusions', function (): void {
    actingAs($this->user);

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

    $response = $this->from(route('user-schedule.index'))
        ->post(route('user-schedule.batch'), [
            'schedules'  => $schedules,
            'delete_ids' => [],
        ]);

    $response->assertRedirect(route('user-schedule.index'))
        ->assertSessionHasErrors(['schedules.7.max_schedules_per_day']);
});

it('validates overlapping schedules in batch request', function (): void {
    actingAs($this->user);

    $batchData = [
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

    $response = $this->post(route('user-schedule.batch'), $batchData);

    $response->assertSessionHasErrors();
});

it('prevents batch deletion of another users schedules', function (): void {
    actingAs($this->user);

    $otherUser = User::factory()->create();
    $otherSchedule = UserSchedule::factory()->create([
        'user_id'     => $otherUser->getKey(),
        'day_of_week' => 1,
        'start_time'  => '09:00',
        'end_time'    => '12:00',
        'type'        => UserScheduleRecordType::WORKING_DAY->value,
    ]);

    $batchData = [
        'schedules'  => [],
        'delete_ids' => [$otherSchedule->getKey()],
    ];

    $response = $this->post(route('user-schedule.batch'), $batchData);

    $response->assertRedirect(route('user-schedule.index'))
        ->assertSessionHas('error', 'Failed to save schedules. Please try again.');

    assertDatabaseHas('user_schedules', [
        'id' => $otherSchedule->getKey(),
    ]);
});

it('ignores client-supplied user_id on create and attributes the new schedule to the authenticated user', function (): void {
    actingAs($this->user);

    $victim = User::factory()->create();
    $victim->assignRole(RoleEnum::MENTOR);

    $batchData = [
        'schedules' => [
            [
                'user_id'     => $victim->getKey(),
                'day_of_week' => 1,
                'start_time'  => '09:00',
                'end_time'    => '17:00',
                'type'        => UserScheduleRecordType::WORKING_DAY->value,
            ],
        ],
        'delete_ids' => [],
    ];

    $response = $this->post(route('user-schedule.batch'), $batchData);

    $response->assertRedirect(route('user-schedule.index'))
        ->assertSessionHas('success');

    assertDatabaseHas('user_schedules', [
        'user_id'     => $this->user->getKey(),
        'day_of_week' => 1,
        'start_time'  => '09:00',
    ]);

    expect(UserSchedule::query()->where('user_id', $victim->getKey())->count())->toBe(0);
});

it('ignores client-supplied user_id on update and keeps the schedule attributed to the authenticated user', function (): void {
    actingAs($this->user);

    $victim = User::factory()->create();
    $victim->assignRole(RoleEnum::MENTOR);

    $schedule = UserSchedule::factory()->create([
        'user_id'     => $this->user->getKey(),
        'day_of_week' => 1,
        'start_time'  => '09:00',
        'end_time'    => '12:00',
        'type'        => UserScheduleRecordType::WORKING_DAY->value,
    ]);

    $batchData = [
        'schedules' => [
            [
                'id'          => $schedule->getKey(),
                'user_id'     => $victim->getKey(),
                'day_of_week' => 1,
                'start_time'  => '10:00',
                'end_time'    => '14:00',
                'type'        => UserScheduleRecordType::WORKING_DAY->value,
            ],
        ],
        'delete_ids' => [],
    ];

    $response = $this->post(route('user-schedule.batch'), $batchData);

    $response->assertRedirect(route('user-schedule.index'))
        ->assertSessionHas('success');

    assertDatabaseHas('user_schedules', [
        'id'         => $schedule->getKey(),
        'user_id'    => $this->user->getKey(),
        'start_time' => '10:00',
        'end_time'   => '14:00',
    ]);

    expect(UserSchedule::query()->where('user_id', $victim->getKey())->count())->toBe(0);
});

it('prevents batch update of another users schedules', function (): void {
    actingAs($this->user);

    $otherUser = User::factory()->create();
    $otherSchedule = UserSchedule::factory()->create([
        'user_id'     => $otherUser->getKey(),
        'day_of_week' => 1,
        'start_time'  => '09:00',
        'end_time'    => '12:00',
        'type'        => UserScheduleRecordType::WORKING_DAY->value,
    ]);

    $batchData = [
        'schedules' => [
            [
                'id'          => $otherSchedule->getKey(),
                'day_of_week' => 1,
                'start_time'  => '10:00',
                'end_time'    => '14:00',
                'type'        => UserScheduleRecordType::WORKING_DAY->value,
            ],
        ],
        'delete_ids' => [],
    ];

    $response = $this->post(route('user-schedule.batch'), $batchData);

    $response->assertRedirect(route('user-schedule.index'))
        ->assertSessionHas('error', 'Failed to save schedules. Please try again.');

    assertDatabaseHas('user_schedules', [
        'id'         => $otherSchedule->getKey(),
        'start_time' => '09:00',
    ]);
});
