<?php

declare(strict_types=1);

namespace App\Actions\Calendar\ExternalCalendar;

use App\Jobs\CreateExternalCalendarEvent;
use App\Jobs\UpdateExternalCalendarEvent;
use App\Models\ExternalCalendarEvent;
use App\Models\UserCalendarIntegration;
use Illuminate\Http\RedirectResponse;
use Lorisleiva\Actions\Concerns\AsController;
use Modules\Calendar\Models\CalendarEvent;

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
