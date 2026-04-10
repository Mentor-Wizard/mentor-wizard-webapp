<?php

declare(strict_types=1);

namespace App\Actions\Calendar;

use App\Enums\CalendarEventStatusEnum;
use App\Enums\CalendarProviderEnum;
use App\Enums\CalendarSyncStatusEnum;
use App\Jobs\SyncCalendarEventToExternalCalendar;
use App\Models\CalendarEvent;
use App\Models\ExternalCalendarEvent;
use App\Models\User;
use App\Models\UserCalendarIntegration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Lorisleiva\Actions\Concerns\AsController;
use Symfony\Component\HttpFoundation\Response;

class ExternalCalendarRetrySync
{
    use AsController;

    public function asController(Request $request, string $provider): RedirectResponse
    {
        abort_unless(CalendarProviderEnum::isValid($provider), Response::HTTP_UNPROCESSABLE_ENTITY);

        $calendarProvider = CalendarProviderEnum::from($provider);

        /** @var User $user */
        $user = $request->user();

        $integration = UserCalendarIntegration::query()
            ->where('user_id', $user->getKey())
            ->where('provider', $calendarProvider)
            ->firstOrFail();

        $integration->update([
            'sync_status'        => CalendarSyncStatusEnum::Active,
            'last_error_message' => null,
        ]);

        $syncedEventIds = ExternalCalendarEvent::query()
            ->where('user_id', $user->getKey())
            ->where('provider', $calendarProvider)
            ->pluck('calendar_event_id');

        CalendarEvent::query()
            ->whereHas('calendarEventUsers', fn ($q) => $q->where('users.id', $user->getKey()))
            ->where('status', CalendarEventStatusEnum::CONFIRMED)
            ->whereNotIn('id', $syncedEventIds)
            ->each(fn (CalendarEvent $event) => dispatch(new SyncCalendarEventToExternalCalendar($event)));

        return to_route('profile.edit')
            ->with('success', 'Calendar sync has been queued. Your events will be synced shortly.');
    }
}
