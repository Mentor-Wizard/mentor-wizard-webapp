<?php

declare(strict_types=1);

namespace App\Actions\Calendar;

use App\Enums\CalendarProviderEnum;
use App\Enums\CalendarSyncStatusEnum;
use App\Jobs\SyncCalendarEventToProvider;
use App\Models\CalendarEvent;
use App\Models\ExternalCalendarEvent;
use App\Models\User;
use App\Models\UserCalendarIntegration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Lorisleiva\Actions\Concerns\AsController;
use Symfony\Component\HttpFoundation\Response;

class ExternalCalendarSyncSingleEvent
{
    use AsController;

    public function asController(Request $request, CalendarEvent $calendarEvent, string $provider): RedirectResponse
    {
        abort_unless(CalendarProviderEnum::isValid($provider), Response::HTTP_UNPROCESSABLE_ENTITY);

        $calendarProvider = CalendarProviderEnum::from($provider);

        /** @var User $user */
        $user = $request->user();

        abort_unless(
            $calendarEvent->calendarEventUsers()->where('users.id', $user->getKey())->exists(),
            Response::HTTP_FORBIDDEN
        );

        $integration = UserCalendarIntegration::query()
            ->where('user_id', $user->getKey())
            ->where('provider', $calendarProvider)
            ->where('sync_status', CalendarSyncStatusEnum::Active)
            ->firstOrFail();

        $alreadySynced = ExternalCalendarEvent::query()
            ->where('calendar_event_id', $calendarEvent->getKey())
            ->where('user_id', $user->getKey())
            ->where('provider', $calendarProvider)
            ->exists();

        abort_if($alreadySynced, Response::HTTP_CONFLICT);

        dispatch(new SyncCalendarEventToProvider($calendarEvent, $integration));

        return back()->with('success', 'Event sync has been queued.');
    }
}
