<?php

declare(strict_types=1);

namespace App\Actions\Calendar\CalendarEvent;

use App\Jobs\CreateExternalCalendarEvent;
use App\Models\UserCalendarIntegration;
use Illuminate\Http\RedirectResponse;
use Lorisleiva\Actions\Concerns\AsController;
use Modules\Calendar\Models\CalendarEvent;

class SyncCalendarEventToIntegration
{
    use AsController;

    public function handle(CalendarEvent $calendarEvent, UserCalendarIntegration $integration): RedirectResponse
    {
        dispatch(new CreateExternalCalendarEvent($calendarEvent, $integration));

        return back()->with('success', 'Sync queued.');
    }
}
