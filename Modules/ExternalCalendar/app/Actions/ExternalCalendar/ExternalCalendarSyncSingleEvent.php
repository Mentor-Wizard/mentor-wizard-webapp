<?php

declare(strict_types=1);

namespace Modules\ExternalCalendar\Actions\ExternalCalendar;

use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Lorisleiva\Actions\Concerns\AsController;
use Modules\Calendar\Models\CalendarEvent;
use Modules\ExternalCalendar\Enums\CalendarSyncStatusEnum;
use Modules\ExternalCalendar\Http\Requests\ExternalCalendarSyncSingleEventRequest;
use Modules\ExternalCalendar\Jobs\CreateExternalCalendarEvent;
use Modules\ExternalCalendar\Models\UserCalendarIntegration;

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
                ->where('sync_status', CalendarSyncStatusEnum::ACTIVE)
                ->firstOrFail();
        } catch (ModelNotFoundException) {
            return back()->with('error', 'No active calendar integration found for this provider.');
        }

        dispatch(new CreateExternalCalendarEvent($calendarEvent, $integration));

        return back()->with('success', 'Event sync has been queued.');
    }
}
