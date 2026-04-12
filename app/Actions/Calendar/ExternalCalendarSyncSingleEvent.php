<?php

declare(strict_types=1);

namespace App\Actions\Calendar;

use App\Http\Requests\Calendar\ExternalCalendarSyncSingleEventRequest;
use App\Jobs\SyncCalendarEventToProvider;
use App\Models\CalendarEvent;
use App\Models\User;
use App\Models\UserCalendarIntegration;
use App\Enums\CalendarSyncStatusEnum;
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

        /** @var UserCalendarIntegration $integration */
        $integration = UserCalendarIntegration::query()
            ->where('user_id', $user->getKey())
            ->where('provider', $calendarProvider)
            ->where('sync_status', CalendarSyncStatusEnum::Active)
            ->first();

        dispatch(new SyncCalendarEventToProvider($calendarEvent, $integration));

        return back()->with('success', 'Event sync has been queued.');
    }
}
