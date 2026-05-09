<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ExternalCalendarEventLog;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ExternalCalendarEventLogPolicy
{
    /**
     * Determine whether the user can acknowledge the external calendar event log.
     */
    public function acknowledge(User $user, ExternalCalendarEventLog $externalCalendarEventLog): Response
    {
        return $user->id === $externalCalendarEventLog->calendarEvent?->mentorProgram?->mentor_id
            ? Response::allow()
            : Response::deny('You do not own this mentor program.');
    }
}
