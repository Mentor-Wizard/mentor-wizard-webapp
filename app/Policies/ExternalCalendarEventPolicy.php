<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ExternalCalendarEvent;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ExternalCalendarEventPolicy
{
    use HandlesAuthorization;

    public function sync(User $user, ExternalCalendarEvent $externalCalendarEvent): bool
    {
        return $user->id === $externalCalendarEvent->user_id;
    }
}
