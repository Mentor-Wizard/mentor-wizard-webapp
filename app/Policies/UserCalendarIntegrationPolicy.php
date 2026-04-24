<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\UserCalendarIntegration;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserCalendarIntegrationPolicy
{
    use HandlesAuthorization;

    public function view(User $user, UserCalendarIntegration $integration): bool
    {
        return $user->id === $integration->user_id;
    }

    public function update(User $user, UserCalendarIntegration $integration): bool
    {
        return $user->id === $integration->user_id;
    }

    public function delete(User $user, UserCalendarIntegration $integration): bool
    {
        return $user->id === $integration->user_id;
    }

    public function sync(User $user, UserCalendarIntegration $integration): bool
    {
        return $user->id === $integration->user_id;
    }
}
