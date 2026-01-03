<?php

declare(strict_types=1);

use App\Actions\UserSchedule\StoreBatchUserSchedule;
use App\Enums\RoleEnum;
use App\Enums\UserScheduleRecordType;
use App\Http\Requests\UserSchedule\StoreBatchUserScheduleRequest;
use App\Models\User;
use App\Models\UserSchedule;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Gate;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\post;

mutates(StoreBatchUserSchedule::class);

describe('StoreBatchUserSchedule', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        $this->user = User::factory()->create();
        $this->user->assignRole(RoleEnum::MENTOR);
        actingAs($this->user);
    });

    it('creates new schedules successfully', function (): void {
        $requestData = [
            'schedules' => [
                [
                    'day_of_week' => 1,
                    'start_time'  => '09:00',
                    'end_time'    => '17:00',
                    'type'        => UserScheduleRecordType::WORKING_DAY->value,
                ],
                [
                    'day_of_week' => 2,
                    'start_time'  => '10:00',
                    'end_time'    => '16:00',
                    'type'        => UserScheduleRecordType::WORKING_DAY->value,
                ],
            ],
            'delete_ids' => [],
        ];

        $request = StoreBatchUserScheduleRequest::create(
            route('user-schedule.batch'),
            'POST',
            $requestData
        );
        $request->setUserResolver(fn () => $this->user);

        Gate::shouldReceive('authorize')->once()->andReturn(true);

        $action = new StoreBatchUserSchedule;
        $response = $action->handle($request);

        expect($response)->toBeInstanceOf(RedirectResponse::class)
            ->and($response->getTargetUrl())->toBe(route('user-schedule.index'));

        expect(UserSchedule::query()->where('user_id', $this->user->id)->count())->toBe(2);
    });

    it('prevents deleting schedules of other users', function (): void {
        $otherUser = User::factory()->create();
        $schedule = UserSchedule::factory()->create(['user_id' => $otherUser->id]);

        post(route('user-schedule.batch'), [
            'schedules'  => [],
            'delete_ids' => [$schedule->id],
        ]);

        expect(UserSchedule::query()->find($schedule->id))->not->toBeNull();
    });

    it('authorizes upsert with schedules and delete ids', function (): void {
        Gate::shouldReceive('authorize')
            ->once()
            ->with('upsert', Mockery::on(fn ($args): bool => $args[0] === UserSchedule::class
                && $args[1] instanceof Collection
                && is_array($args[2])));

        $schedule = UserSchedule::factory()->create(['user_id' => $this->user->id]);

        $request = new StoreBatchUserScheduleRequest([
            'schedules' => [
                ['id' => $schedule->id, 'day_of_week' => 1, 'start_time' => '09:00', 'end_time' => '17:00'],
            ],
            'delete_ids' => [$schedule->id],
        ]);

        $action = new StoreBatchUserSchedule;
        $action->handle($request);
    });

    it('updates existing schedules', function (): void {
        $schedule = UserSchedule::factory()->create([
            'user_id'     => $this->user->id,
            'day_of_week' => 1,
            'start_time'  => '09:00',
            'end_time'    => '17:00',
        ]);

        $requestData = [
            'schedules' => [
                [
                    'id'          => $schedule->id,
                    'user_id'     => $this->user->getKey(),
                    'day_of_week' => 1,
                    'start_time'  => '10:00',
                    'end_time'    => '18:00',
                    'type'        => UserScheduleRecordType::WORKING_DAY->value,
                ],
            ],
            'delete_ids' => [],
        ];

        $request = StoreBatchUserScheduleRequest::create(
            route('user-schedule.batch'),
            'POST',
            $requestData
        );
        $request->setUserResolver(fn () => $this->user);

        Gate::shouldReceive('authorize')->once()->andReturn(true);

        $action = new StoreBatchUserSchedule;
        $response = $action->handle($request);
        expect($response)->toBeInstanceOf(RedirectResponse::class);

        $schedule->refresh();
        expect($schedule->start_time)->toBe('10:00:00')
            ->and($schedule->end_time)->toBe('18:00:00');
    });

    it('deletes schedules', function (): void {
        $schedule1 = UserSchedule::factory()->create(['user_id' => $this->user->id]);
        $schedule2 = UserSchedule::factory()->create(['user_id' => $this->user->id]);

        $requestData = [
            'schedules'  => [],
            'delete_ids' => [$schedule1->id, $schedule2->id],
        ];

        $request = StoreBatchUserScheduleRequest::create(
            route('user-schedule.batch'),
            'POST',
            $requestData
        );
        $request->setUserResolver(fn () => $this->user);

        Gate::shouldReceive('authorize')->once()->andReturn(true);

        $action = new StoreBatchUserSchedule;
        $response = $action->handle($request);

        expect($response)->toBeInstanceOf(RedirectResponse::class);

        expect(UserSchedule::query()->where('user_id', $this->user->id)->count())->toBe(0);
    });

    it('check authorization, when send request to delete schedules from other user', function (): void {
        $anotherMentor = User::factory()->create();
        $anotherMentor->assignRole(RoleEnum::MENTOR);

        $schedule1 = UserSchedule::factory()->create(['user_id' => $this->user->id]);
        $schedule2 = UserSchedule::factory()->create(['user_id' => $anotherMentor->id]);

        $requestData = [
            'schedules'  => [],
            'delete_ids' => [$schedule1->id, $schedule2->id],
        ];

        $request = StoreBatchUserScheduleRequest::create(
            route('user-schedule.batch'),
            'POST',
            $requestData
        );
        $request->setUserResolver(fn () => $this->user);

        $action = new StoreBatchUserSchedule;
        $response = $action->handle($request);

        expect($response)->toBeInstanceOf(RedirectResponse::class);

        expect(UserSchedule::query()->where('user_id', $this->user->id)->count())->toBe(1);
        expect(UserSchedule::query()->where('user_id', $anotherMentor->id)->count())->toBe(1);
    });

    it('handles mixed create, update, and delete operations', function (): void {
        $existingSchedule = UserSchedule::factory()->create([
            'user_id'     => $this->user->id,
            'day_of_week' => 1,
        ]);

        $scheduleToDelete = UserSchedule::factory()->create([
            'user_id'     => $this->user->id,
            'day_of_week' => 3,
        ]);

        $requestData = [
            'schedules' => [
                [
                    'id'          => $existingSchedule->id,
                    'user_id'     => $this->user->getKey(),
                    'day_of_week' => 1,
                    'start_time'  => '10:00',
                    'end_time'    => '18:00',
                    'type'        => UserScheduleRecordType::WORKING_DAY->value,
                ],
                [
                    'day_of_week' => 2,
                    'start_time'  => '09:00',
                    'end_time'    => '17:00',
                    'type'        => UserScheduleRecordType::WORKING_DAY->value,
                ],
            ],
            'delete_ids' => [$scheduleToDelete->id],
        ];

        $request = StoreBatchUserScheduleRequest::create(
            route('user-schedule.batch'),
            'POST',
            $requestData
        );
        $request->setUserResolver(fn () => $this->user);

        Gate::shouldReceive('authorize')->once()->andReturn(true);

        $action = new StoreBatchUserSchedule;
        $response = $action->handle($request);

        expect($response)->toBeInstanceOf(RedirectResponse::class);

        expect(UserSchedule::query()->where('user_id', $this->user->id)->count())->toBe(2);
        expect(UserSchedule::query()->find($scheduleToDelete->id))->toBeNull();

        $existingSchedule->refresh();
        expect($existingSchedule->start_time)->toBe('10:00:00');
    });

    it('handles rollback during mixed create, update, and delete operations', function (): void {
        $existingSchedule = UserSchedule::factory()->create([
            'user_id'     => $this->user->id,
            'day_of_week' => 1,
        ]);

        $scheduleToDelete = UserSchedule::factory()->create([
            'user_id'     => $this->user->id,
            'day_of_week' => 3,
        ]);

        // wrong payload structure
        $requestData = [
            'schedules' => [[
                [
                    'id'          => $existingSchedule->id,
                    'user_id'     => $this->user->getKey(),
                    'day_of_week' => 4,
                    'start_time'  => '10:00',
                    'end_time'    => '18:00',
                    'type'        => UserScheduleRecordType::WORKING_DAY->value,
                ],
                [
                    'day_of_week' => 5,
                    'start_time'  => '09:00',
                    'end_time'    => '17:00',
                    'type'        => UserScheduleRecordType::WORKING_DAY->value,
                ],
            ]],
            'delete_ids' => [$scheduleToDelete->id],
        ];

        $request = StoreBatchUserScheduleRequest::create(
            route('user-schedule.batch'),
            'POST',
            $requestData
        );
        $request->setUserResolver(fn () => $this->user);

        $action = new StoreBatchUserSchedule;
        $response = $action->handle($request);

        expect($response)->toBeInstanceOf(RedirectResponse::class);

        // deletion happens prior to create and update. So rollback should leave scheduleToDelete
        expect(UserSchedule::query()->where('id', $scheduleToDelete->id)->exists());

        $existingSchedule->refresh();
        expect($existingSchedule->start_time)->toBe('09:00:00');
    });

    it('returns redirect with success message on successful operation', function (): void {
        $requestData = [
            'schedules' => [
                [
                    'day_of_week' => 1,
                    'start_time'  => '09:00',
                    'end_time'    => '17:00',
                    'type'        => UserScheduleRecordType::WORKING_DAY->value,
                ],
            ],
            'delete_ids' => [],
        ];

        $request = StoreBatchUserScheduleRequest::create(
            route('user-schedule.batch'),
            'POST',
            $requestData
        );
        $request->setUserResolver(fn () => $this->user);

        Gate::shouldReceive('authorize')->once()->andReturn(true);

        $action = new StoreBatchUserSchedule;
        $response = $action->handle($request);

        expect($response->getSession()->get('success'))->toBe('Schedules were saved successfully.');
    });

    it('handles day off schedules with dates', function (): void {
        $dayOffDate = Date::now()->addWeek();

        $requestData = [
            'schedules' => [
                [
                    'day_of_week'  => 1,
                    'start_time'   => '00:00',
                    'end_time'     => '23:59',
                    'type'         => UserScheduleRecordType::DAY_OFF->value,
                    'day_off_date' => $dayOffDate->format('Y-m-d'),
                ],
            ],
            'delete_ids' => [],
        ];

        $request = StoreBatchUserScheduleRequest::create(
            route('user-schedule.batch'),
            'POST',
            $requestData
        );
        $request->setUserResolver(fn () => $this->user);

        Gate::shouldReceive('authorize')->once()->andReturn(true);

        $action = new StoreBatchUserSchedule;
        $response = $action->handle($request);

        expect($response)->toBeInstanceOf(RedirectResponse::class);

        $schedule = UserSchedule::query()->where('user_id', $this->user->id)->first();
        expect($schedule->type)->toBe(UserScheduleRecordType::DAY_OFF)
            ->and($schedule->day_off_date->format('Y-m-d'))->toBe($dayOffDate->format('Y-m-d'));
    });

    it('only deletes schedules owned by authenticated user', function (): void {
        $schedule = UserSchedule::factory()->create(['user_id' => $this->user->id]);

        $requestData = [
            'schedules'  => [],
            'delete_ids' => [$schedule->id],
        ];

        $request = StoreBatchUserScheduleRequest::create(
            route('user-schedule.batch'),
            'POST',
            $requestData
        );
        $request->setUserResolver(fn () => $this->user);

        Gate::shouldReceive('authorize')->once()->andReturn(true);

        $action = new StoreBatchUserSchedule;
        $action->handle($request);

        expect(UserSchedule::query()->find($schedule->id))->toBeNull();
    });

    it('filters fillable fields correctly for updates', function (): void {
        $schedule = UserSchedule::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $requestData = [
            'schedules' => [
                [
                    'id'           => $schedule->id,
                    'day_of_week'  => 1,
                    'start_time'   => '09:00',
                    'end_time'     => '17:00',
                    'type'         => UserScheduleRecordType::WORKING_DAY->value,
                    'extra_field'  => 'should be filtered',
                ],
            ],
            'delete_ids' => [],
        ];

        $request = StoreBatchUserScheduleRequest::create(
            route('user-schedule.batch'),
            'POST',
            $requestData
        );
        $request->setUserResolver(fn () => $this->user);

        Gate::shouldReceive('authorize')->once()->andReturn(true);

        $action = new StoreBatchUserSchedule;
        $response = $action->handle($request);

        expect($response)->toBeInstanceOf(RedirectResponse::class);
    });

    it('adds user_id to new schedules', function (): void {
        $requestData = [
            'schedules' => [
                [
                    'day_of_week' => 1,
                    'start_time'  => '09:00',
                    'end_time'    => '17:00',
                    'type'        => UserScheduleRecordType::WORKING_DAY->value,
                ],
            ],
            'delete_ids' => [],
        ];

        $request = StoreBatchUserScheduleRequest::create(
            route('user-schedule.batch'),
            'POST',
            $requestData
        );
        $request->setUserResolver(fn () => $this->user);

        Gate::shouldReceive('authorize')->once()->andReturn(true);

        $action = new StoreBatchUserSchedule;
        $action->handle($request);

        $schedule = UserSchedule::query()->where('user_id', $this->user->id)->first();
        expect($schedule)->not()->toBeNull()
            ->and($schedule->user_id)->toBe($this->user->id);
    });

    it('handles empty schedules array', function (): void {
        $requestData = [
            'schedules'  => [],
            'delete_ids' => [],
        ];

        $request = StoreBatchUserScheduleRequest::create(
            route('user-schedule.batch'),
            'POST',
            $requestData
        );
        $request->setUserResolver(fn () => $this->user);

        Gate::shouldReceive('authorize')->once()->andReturn(true);

        $action = new StoreBatchUserSchedule;
        $response = $action->handle($request);

        expect($response)->toBeInstanceOf(RedirectResponse::class)
            ->and($response->getSession()?->get('success'))->toBe('Schedules were saved successfully.');
    });

    it('test commit error, when user is deleted new schedules successfully', function (): void {
        $requestData = [
            'schedules' => [
                [
                    'day_of_week' => 1,
                    'start_time'  => '09:00',
                    'end_time'    => '17:00',
                    'type'        => UserScheduleRecordType::WORKING_DAY->value,
                ],
                [
                    'day_of_week' => 2,
                    'start_time'  => '10:00',
                    'end_time'    => '16:00',
                    'type'        => UserScheduleRecordType::WORKING_DAY->value,
                ],
            ],
            'delete_ids' => [],
        ];

        $request = StoreBatchUserScheduleRequest::create(
            route('user-schedule.batch'),
            'POST',
            $requestData
        );
        $request->setUserResolver(fn () => $this->user);

        Gate::shouldReceive('authorize')->once()->andReturn(true);

        $this->user->delete();
        $action = new StoreBatchUserSchedule;
        $response = $action->handle($request);

        expect($response)->toBeInstanceOf(RedirectResponse::class)
            ->and($response->getTargetUrl())->toBe(route('user-schedule.index'))
            ->and($response->getSession()->get('error'))->toBe('Failed to save schedules. Please try again.');

        expect(UserSchedule::query()->count())->toBe(0);
    });

    it('commits transaction and persists data after successful operations', function (): void {
        $requestData = [
            'schedules' => [
                [
                    'day_of_week' => 1,
                    'start_time'  => '09:00',
                    'end_time'    => '17:00',
                    'type'        => UserScheduleRecordType::WORKING_DAY->value,
                ],
            ],
            'delete_ids' => [],
        ];

        $request = StoreBatchUserScheduleRequest::create(
            route('user-schedule.batch'),
            'POST',
            $requestData
        );
        $request->setUserResolver(fn () => $this->user);

        Gate::shouldReceive('authorize')->once()->andReturn(true);

        $action = new StoreBatchUserSchedule;
        $response = $action->handle($request);

        expect($response)->toBeInstanceOf(RedirectResponse::class);

        $this->assertDatabaseHas('user_schedules', [
            'user_id'     => $this->user->id,
            'day_of_week' => 1,
            'start_time'  => '09:00:00',
            'end_time'    => '17:00:00',
        ]);
    });

    it('creates, updates, deletes schedules and commits the transaction', function (): void {
        $user = User::factory()->create();
        $this->actingAs($user);

        $fillable = new UserSchedule()->getFillable();

        $existingToUpdate = UserSchedule::factory()->create([
            'user_id' => $user->id,
        ]);

        $existingToDelete = UserSchedule::factory()->create([
            'user_id' => $user->id,
        ]);

        $updateAttrs = Arr::only(
            UserSchedule::factory()->make(['user_id' => $user->id])->toArray(),
            $fillable
        );

        $createAttrs = Arr::only(
            UserSchedule::factory()->make()->toArray(),
            $fillable
        );

        $schedulesPayload = [
            array_merge(['id' => $existingToUpdate->id], $updateAttrs),
            $createAttrs,
        ];

        $deleteIds = [$existingToDelete->id];

        $request = Mockery::mock(StoreBatchUserScheduleRequest::class);
        $request->shouldReceive('input')
            ->with('schedules', [])
            ->andReturn($schedulesPayload);
        $request->shouldReceive('input')
            ->with('delete_ids', [])
            ->andReturn($deleteIds);

        $response = new StoreBatchUserSchedule()->handle($request);

        expect($response->isRedirect())->toBeTrue();

        // Deleted
        expect(UserSchedule::query()->whereKey($existingToDelete->id)->exists())->toBeFalse();

        $existingToUpdate->refresh();

        expect(UserSchedule::query()->where('user_id', $user->id)->exists())->toBeTrue();

        // This is what kills the "RemoveMethodCall: DB::commit()" mutation.
        expect(DB::transactionLevel())->toBe(1);
    });

    it('does not delete schedules belonging to a different user', function (): void {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->actingAs($user);

        $otherUsersSchedule = UserSchedule::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $request = Mockery::mock(StoreBatchUserScheduleRequest::class);
        $request->shouldReceive('input')
            ->with('schedules', [])
            ->andReturn([]);
        $request->shouldReceive('input')
            ->with('delete_ids', [])
            ->andReturn([$otherUsersSchedule->id]);

        $response = new StoreBatchUserSchedule()->handle($request);

        expect($response->isRedirect())->toBeTrue();
        expect(UserSchedule::query()->whereKey($otherUsersSchedule->id)->exists())->toBeTrue();
        expect(DB::transactionLevel())->toBe(1);
    });

});
