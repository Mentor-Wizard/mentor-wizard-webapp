<?php

declare(strict_types=1);

namespace App\Actions\UserSchedule;

use App\Http\Requests\UserSchedule\StoreBatchUserScheduleRequest;
use App\Models\User;
use App\Models\UserSchedule;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\Concerns\AsController;

class StoreBatchUserSchedule
{
    use AsController;

    public function handle(StoreBatchUserScheduleRequest $request): RedirectResponse
    {
        try {
            DB::beginTransaction();

            $userId = auth()->user()->id;

            $schedules = collect($request->input('schedules', []));
            $deleteIds = $request->input('delete_ids', []);

            Gate::authorize('upsert', [UserSchedule::class, $schedules, (array) $deleteIds]);

            if (! empty($deleteIds)) {
                UserSchedule::query()
                    ->whereIn('id', $deleteIds)
                    ->where('user_id', $userId)
                    ->delete();
            }

            $fillableFields = (new UserSchedule)->getFillable();

            $updateSchedules = collect($schedules)->map(function ($schedule) use ($fillableFields) {
                if (isset($schedule['id'])) {
                    return Arr::only($schedule, array_merge($fillableFields, ['id']));
                }
            })->filter()->toArray();

            $createSchedules = collect($schedules)->map(function ($schedule) use ($fillableFields, $userId) {
                if (! isset($schedule['id'])) {
                    return Arr::add(Arr::only($schedule, array_merge($fillableFields, ['id'])), 'user_id', $userId);
                }
            })->filter()->toArray();

            DB::table('user_schedules')
                ->upsert($updateSchedules, 'id', $fillableFields);
            DB::table('user_schedules')->insert($createSchedules);

            DB::commit();

            return redirect()
                ->route('user-schedule.index')
                ->with('success', 'Schedules saved successfully.');
        } catch (Exception $exception) {
            DB::rollBack();

            return redirect()
                ->route('user-schedule.index')
                ->with('error', 'Failed to save schedules. Please try again.');
        }
    }
}
