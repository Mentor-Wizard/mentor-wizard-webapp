<?php

declare(strict_types=1);

namespace App\Actions\UserSchedule;

use App\Http\Requests\UserSchedule\StoreBatchUserScheduleRequest;
use App\Models\UserSchedule;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Lorisleiva\Actions\Concerns\AsController;

class StoreBatchUserSchedule
{
    use AsController;

    public function handle(StoreBatchUserScheduleRequest $request): RedirectResponse
    {
        try {
            DB::beginTransaction();

            $userId = auth()->user()->id;
            /** @var array<int, mixed> $schedulesData */
            $schedulesData = $request->input('schedules', []);
            /** @var Collection <int, mixed> $schedules */
            $schedules = collect($schedulesData);
            /** @var array<int, int> $deleteIds */
            $deleteIds = $request->input('delete_ids', []);

            // Added gate here, to check each schedule record, on case of possibility to update it
            Gate::authorize('upsert', [UserSchedule::class, $schedules, $deleteIds]);

            if (! empty($deleteIds)) {
                UserSchedule::query()
                    ->whereIn('id', $deleteIds)
                    ->where('user_id', $userId)
                    ->delete();
            }

            $fillableFields = (new UserSchedule)->getFillable();
            $updateSchedules = collect($schedules)->map(function (array $schedule) use ($fillableFields) {
                if (isset($schedule['id'])) {
                    return Arr::only($schedule, array_merge($fillableFields, ['id']));
                }
            })->filter()->all();
            $createSchedules = collect($schedules)->map(function (array $schedule) use ($fillableFields, $userId) {
                if (! isset($schedule['id'])) {
                    return Arr::add(Arr::only($schedule,
                        $fillableFields),
                        'user_id', $userId);
                }
            })->filter()->all();

            DB::table('user_schedules')
                ->upsert($updateSchedules, 'id', $fillableFields);
            DB::table('user_schedules')->insert($createSchedules);

            // @pest-mutate-ignore-next-line
            DB::commit();

            return to_route('user-schedule.index')
                ->with('success', 'Schedules were saved successfully.');
        } catch (Exception) {
            DB::rollBack();

            return to_route('user-schedule.index')
                ->with('error', 'Failed to save schedules. Please try again.');
        }
    }
}
