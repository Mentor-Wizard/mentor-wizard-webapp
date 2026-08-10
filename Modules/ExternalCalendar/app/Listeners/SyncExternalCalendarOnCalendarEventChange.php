<?php

declare(strict_types=1);

namespace Modules\ExternalCalendar\Listeners;

use Modules\Calendar\Events\CalendarEventCancelled;
use Modules\Calendar\Events\CalendarEventConfirmed;
use Modules\Calendar\Events\CalendarEventContentChanged;
use Modules\Calendar\Events\CalendarEventDeleting;
use Modules\ExternalCalendar\Jobs\ProcessCalendarEventExternalCalendarIntegrations;
use Modules\ExternalCalendar\Jobs\ProcessDeleteExternalCalendarEvent;
use Modules\ExternalCalendar\Jobs\ProcessUpdateExternalCalendarEvent;

/**
 * Translates `Modules\Calendar` domain events into `Modules\ExternalCalendar` sync jobs.
 *
 * Deliberately synchronous (does not implement `ShouldQueue`): each handler only
 * dispatches a job, it does no work itself, so the existing semantics — event fired
 * synchronously within the request/observer, sync work happens in the queue — are
 * preserved without a second serialization of the model.
 */
class SyncExternalCalendarOnCalendarEventChange
{
    public function handleConfirmed(CalendarEventConfirmed $event): void
    {
        dispatch(new ProcessCalendarEventExternalCalendarIntegrations($event->calendarEvent));
    }

    public function handleCancelled(CalendarEventCancelled $event): void
    {
        dispatch(new ProcessDeleteExternalCalendarEvent($event->calendarEventId));
    }

    public function handleContentChanged(CalendarEventContentChanged $event): void
    {
        dispatch(new ProcessUpdateExternalCalendarEvent($event->calendarEvent));
    }

    public function handleDeleting(CalendarEventDeleting $event): void
    {
        dispatch(new ProcessDeleteExternalCalendarEvent($event->calendarEventId));
    }
}
