<?php

declare(strict_types=1);

namespace Modules\ExternalCalendar\Actions\ExternalCalendar;

use Illuminate\Http\RedirectResponse;
use Lorisleiva\Actions\Concerns\AsController;
use Modules\Calendar\Models\CalendarEvent;
use Modules\ExternalCalendar\Jobs\CreateExternalCalendarEvent;
use Modules\ExternalCalendar\Jobs\UpdateExternalCalendarEvent;
use Modules\ExternalCalendar\Models\ExternalCalendarEvent;
use Modules\ExternalCalendar\Models\UserCalendarIntegration;

class RerunExternalCalendarEventSync
{
    use AsController;

    public function handle(CalendarEvent $calendarEvent, ExternalCalendarEvent $externalCalendarEvent): RedirectResponse
    {
        $integration = UserCalendarIntegration::query()
            ->where('user_id', $externalCalendarEvent->user_id)
            ->where('provider', $externalCalendarEvent->provider)
            ->firstOrFail();

        if ($externalCalendarEvent->external_event_id === null) {
            dispatch(new CreateExternalCalendarEvent($calendarEvent, $integration));
        } else {
            dispatch(new UpdateExternalCalendarEvent($calendarEvent, $externalCalendarEvent, $integration));
        }

        return back()->with('success', 'Sync has been queued.');
    }
}
