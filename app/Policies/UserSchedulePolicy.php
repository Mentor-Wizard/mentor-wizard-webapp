<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\UserSchedule;
use Illuminate\Support\Collection;

class UserSchedulePolicy
{
    /**
     * Create a new policy instance.
     *
     * @param  Collection<int, array{id?: int}>  $scheduleBatch
     * @param  array<int, int>  $deleteIds
     */
    public function upsert(User $user, Collection $scheduleBatch, array $deleteIds): bool
    {
        $updateIds = collect($scheduleBatch)
            ->pluck('id')
            ->filter()
            ->values()
            ->toArray();

        $allIdsToCheck = array_merge($updateIds, $deleteIds);

        return ! UserSchedule::query()
            ->whereIn('id', $allIdsToCheck)
            ->where(function ($query) use ($user): void {
                $query->where('user_id', '!=', $user->id)
                    ->orWhereNull('user_id');
            })
            ->exists();
    }
}
