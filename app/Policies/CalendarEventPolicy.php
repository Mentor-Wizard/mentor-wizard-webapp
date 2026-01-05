<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\CalendarEvent;
use App\Models\User;

final class CalendarEventPolicy
{
    public function update(User $user, CalendarEvent $calendarEvent): bool
    {
        // Only the mentor of the related mentor program can update events (web_link only at application level)
        $mentorProgram = $calendarEvent->MentorProgram()->first();
        if ($mentorProgram === null) {
            return false;
        }

        return (int) $mentorProgram->mentor_id === (int) $user->getKey();
    }

    public function delete(User $user, CalendarEvent $calendarEvent): bool
    {
        // Mentor of the program OR any attached participant (mentee/host/cohost) can delete
        $mentorProgram = $calendarEvent->MentorProgram()->first();
        $isMentor = $mentorProgram !== null && (int) $mentorProgram->mentor_id === (int) $user->getKey();

        if ($isMentor) {
            return true;
        }

        if ($calendarEvent->relationLoaded('calendarEventUsers')) {
            return $calendarEvent->calendarEventUsers
                ->where('id', $user->getKey())
                ->isNotEmpty();
        }

        return $calendarEvent->calendarEventUsers()
            ->where('user_id', $user->getKey())
            ->exists();
    }

    public function view(User $user, CalendarEvent $calendarEvent): bool
    {
        if ($calendarEvent->relationLoaded('calendarEventUsers')) {
            return $calendarEvent->calendarEventUsers
                ->where('id', $user->getKey())
                ->isNotEmpty();
        }

        return $calendarEvent->calendarEventUsers()
            ->where('user_id', $user->getKey())
            ->exists();
    }
}
