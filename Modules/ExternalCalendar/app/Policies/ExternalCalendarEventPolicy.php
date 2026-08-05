<?php

declare(strict_types=1);

namespace Modules\ExternalCalendar\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\ExternalCalendar\Models\ExternalCalendarEvent;

class ExternalCalendarEventPolicy
{
    use HandlesAuthorization;

    public function sync(User $user, ExternalCalendarEvent $externalCalendarEvent): bool
    {
        return $user->id === $externalCalendarEvent->user_id;
    }
}
