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
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Gate;

use function Pest\Laravel\actingAs;

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
});
