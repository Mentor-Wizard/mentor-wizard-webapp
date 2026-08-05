<?php

declare(strict_types=1);

namespace Modules\ExternalCalendar\Actions\ExternalCalendar;

use Illuminate\Http\RedirectResponse;
use Lorisleiva\Actions\Concerns\AsController;
use Modules\Calendar\Models\CalendarEvent;
use Modules\ExternalCalendar\Jobs\CreateExternalCalendarEvent;
use Modules\ExternalCalendar\Models\UserCalendarIntegration;

class SyncCalendarEventToIntegration
{
    use AsController;

    public function handle(CalendarEvent $calendarEvent, UserCalendarIntegration $integration): RedirectResponse
    {
        dispatch(new CreateExternalCalendarEvent($calendarEvent, $integration));

        return back()->with('success', 'Sync queued.');
    }
}
