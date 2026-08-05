<?php

declare(strict_types=1);

namespace Modules\ExternalCalendar\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\ExternalCalendar\Models\UserCalendarIntegration;

class UserCalendarIntegrationPolicy
{
    use HandlesAuthorization;

    public function view(User $user, UserCalendarIntegration $integration): bool
    {
        return $user->getKey() === $integration->user_id;
    }

    public function update(User $user, UserCalendarIntegration $integration): bool
    {
        return $user->getKey() === $integration->user_id;
    }

    public function delete(User $user, UserCalendarIntegration $integration): bool
    {
        return $user->getKey() === $integration->user_id;
    }

    public function sync(User $user, UserCalendarIntegration $integration): bool
    {
        return $user->getKey() === $integration->user_id;
    }
}
