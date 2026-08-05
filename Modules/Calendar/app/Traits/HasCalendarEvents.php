<?php

declare(strict_types=1);

namespace Modules\Calendar\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\Calendar\Enums\CalendarEventRoleEnum;
use Modules\Calendar\Models\CalendarEvent;

/**
 * @phpstan-require-extends Model
 */
trait HasCalendarEvents
{
    /**
     * @return BelongsToMany<CalendarEvent, static>
     */
    public function calendarEvents(): BelongsToMany
    {
        /** @phpstan-ignore-next-line */
        return $this->belongsToMany(CalendarEvent::class,
            'calendar_event_user', 'user_id')
            ->withPivot('colour')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * @return BelongsToMany<CalendarEvent, static>
     */
    public function hostedCalendarEvents(): BelongsToMany
    {
        return $this->calendarEvents()
            ->wherePivot('role', CalendarEventRoleEnum::HOST);
    }

    /**
     * @return BelongsToMany<CalendarEvent, static>
     */
    public function participatingCalendarEvents(): BelongsToMany
    {
        return $this->calendarEvents()
            ->wherePivot('role', CalendarEventRoleEnum::PARTICIPANT);
    }
}
