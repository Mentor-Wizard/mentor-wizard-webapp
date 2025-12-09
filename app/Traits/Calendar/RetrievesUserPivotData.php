<?php

declare(strict_types=1);

namespace App\Traits\Calendar;

use App\Models\CalendarEvent;
use App\Models\User;

trait RetrievesUserPivotData
{
    /**
     * Get user's pivot colour for the calendar event.
     */
    protected static function getUserColour(CalendarEvent $event, ?User $user): ?string
    {
        if (! $user instanceof User) {
            return null;
        }

        return $event->calendarEventUsers
            ->firstWhere('id', $user->getKey())
            ?->pivot
            ?->colour;
    }
}
