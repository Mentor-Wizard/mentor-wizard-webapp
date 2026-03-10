<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\CalendarEvent;
use App\Models\User;

final class CalendarEventPolicy
{
    public function create(User $user): bool
    {
        return $user->hasVerifiedEmail();
    }

    public function update(User $user, CalendarEvent $calendarEvent): bool
    {
        // Only the mentor of the related mentor program can update events
        $mentorProgram = $calendarEvent->mentorProgram()->first();
        if ($mentorProgram === null) {
            return false;
        }

        return $mentorProgram->mentor_id === $user->getKey();
    }

    public function delete(User $user, CalendarEvent $calendarEvent): bool
    {
        // Mentor of the program OR any attached participant (mentee/host/cohost) can delete
        $mentorProgram = $calendarEvent->mentorProgram()->first();
        $isMentor = $mentorProgram !== null && $mentorProgram->mentor_id === $user->getKey();

        if ($isMentor) {
            return true;
        }

        return $calendarEvent->relationLoaded('calendarEventUsers')
            ? $calendarEvent->calendarEventUsers
                ->where('id', $user->getKey())
                ->isNotEmpty()
            : $calendarEvent->calendarEventUsers()
                ->where('user_id', $user->getKey())
                ->exists();
    }

    public function view(User $user, CalendarEvent $calendarEvent): bool
    {
        return $calendarEvent->relationLoaded('calendarEventUsers')
            ? $calendarEvent->calendarEventUsers
                ->where('id', $user->getKey())
                ->isNotEmpty()
            : $calendarEvent->calendarEventUsers()
                ->where('user_id', $user->getKey())
                ->exists();
    }
}
