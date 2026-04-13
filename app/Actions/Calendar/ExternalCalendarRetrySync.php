<?php

declare(strict_types=1);

namespace App\Actions\Calendar;

use App\Enums\CalendarEventStatusEnum;
use App\Enums\CalendarSyncStatusEnum;
use App\Http\Requests\Calendar\ExternalCalendarRetrySyncRequest;
use App\Jobs\ProcessCalendarEventExternalCalendarIntegrations;
use App\Models\CalendarEvent;
use App\Models\ExternalCalendarEvent;
use App\Models\User;
use App\Models\UserCalendarIntegration;
use Illuminate\Http\RedirectResponse;
use Lorisleiva\Actions\Concerns\AsController;

class ExternalCalendarRetrySync
{
    use AsController;

    public function handle(ExternalCalendarRetrySyncRequest $request): RedirectResponse
    {
        $calendarProvider = $request->resolveProvider();

        /** @var User $user */
        $user = $request->user();

        $integration = UserCalendarIntegration::query()
            ->where('user_id', $user->getKey())
            ->where('provider', $calendarProvider)
            ->first();

        if ($integration === null) {
            return to_route('profile.edit')
                ->with('error', 'No calendar integration found for this provider.');
        }

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
            ->each(fn (CalendarEvent $event) => dispatch(new ProcessCalendarEventExternalCalendarIntegrations($event)));

        return to_route('profile.edit')
            ->with('success', 'Calendar sync has been queued. Your events will be synced shortly.');
    }
}
