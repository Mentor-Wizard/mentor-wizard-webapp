<?php

declare(strict_types=1);

namespace App\Actions\Calendar\ExternalCalendar;

use App\Enums\CalendarSyncStatusEnum;
use App\Http\Requests\Calendar\ExternalCalendarSyncSingleEventRequest;
use App\Jobs\CreateExternalCalendarEvent;
use App\Models\CalendarEvent;
use App\Models\User;
use App\Models\UserCalendarIntegration;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Lorisleiva\Actions\Concerns\AsController;

class ExternalCalendarSyncSingleEvent
{
    use AsController;

    public function handle(ExternalCalendarSyncSingleEventRequest $request, CalendarEvent $calendarEvent): RedirectResponse
    {
        $calendarProvider = $request->resolveProvider();

        /** @var User $user */
        $user = $request->user();

        try {
            $integration = UserCalendarIntegration::query()
                ->where('user_id', $user->getKey())
                ->where('provider', $calendarProvider)
                ->where('sync_status', CalendarSyncStatusEnum::Active)
                ->firstOrFail();
        } catch (ModelNotFoundException) {
            return back()->with('error', 'No active calendar integration found for this provider.');
        }

        dispatch(new CreateExternalCalendarEvent($calendarEvent, $integration));

        return back()->with('success', 'Event sync has been queued.');
    }
}
