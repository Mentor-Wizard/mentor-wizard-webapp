<?php

declare(strict_types=1);

namespace Modules\Calendar\Traits;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Modules\Calendar\Models\CalendarEvent;

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

        $pivot = $event->calendarEventUsers
            ->firstWhere('id', $user->getKey())
            ?->pivot;

        /** @var Pivot|null $pivot */

        return $pivot?->getAttribute('colour');
    }
}
