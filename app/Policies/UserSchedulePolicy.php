<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Illuminate\Support\Collection;

class UserSchedulePolicy
{
    /**
     * Create a new policy instance.
     */
    public function upsert(User $user, Collection $scheduleBatch, array $deleteIds): bool
    {
        $updateIds = collect($scheduleBatch)
            ->pluck('id')
            ->filter()
            ->values()
            ->toArray();

        $allIdsToCheck = array_merge($updateIds, $deleteIds);

        if ($allIdsToCheck === []) {
            return true;
        }

        return ! $user->schedules()
            ->whereIn('id', $allIdsToCheck)
            ->where('user_id', '!=', $user->id)
            ->exists();
    }
}
