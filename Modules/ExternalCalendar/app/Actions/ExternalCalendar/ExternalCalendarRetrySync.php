<?php

declare(strict_types=1);

namespace Modules\ExternalCalendar\Actions\ExternalCalendar;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Date;
use Lorisleiva\Actions\Concerns\AsController;
use Modules\Calendar\Enums\CalendarEventStatusEnum;
use Modules\Calendar\Models\CalendarEvent;
use Modules\ExternalCalendar\Enums\CalendarProviderEnum;
use Modules\ExternalCalendar\Enums\CalendarSyncStatusEnum;
use Modules\ExternalCalendar\Http\Requests\ExternalCalendarRetrySyncRequest;
use Modules\ExternalCalendar\Jobs\ProcessCalendarEventExternalCalendarIntegrations;
use Modules\ExternalCalendar\Models\ExternalCalendarEvent;
use Modules\ExternalCalendar\Models\UserCalendarIntegration;

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

        abort_if($user->cannot('sync', $integration), 403);

        $integration->update([
            'sync_status'        => CalendarSyncStatusEnum::ACTIVE,
            'last_error_message' => null,
        ]);

        $this->buildSyncJobs($user, $calendarProvider);

        return to_route('profile.edit')
            ->with('success', 'Calendar sync has been queued. Your events will be synced shortly.');
    }

    private function buildSyncJobs(User $user, CalendarProviderEnum $calendarProvider): void
    {
        $syncedSubquery = ExternalCalendarEvent::query()
            ->select('calendar_event_id')
            ->where('user_id', $user->getKey())
            ->where('provider', $calendarProvider);

        $jobs = CalendarEvent::query()
            ->whereHas('calendarEventUsers', fn ($q) => $q->where('users.id', $user->getKey()))
            ->where('status', CalendarEventStatusEnum::CONFIRMED)
            ->where('start_date_time', '>=', Date::now())
            ->whereNotIn('id', $syncedSubquery)
            ->get()
            ->map(fn (CalendarEvent $event): ProcessCalendarEventExternalCalendarIntegrations => new ProcessCalendarEventExternalCalendarIntegrations($event))
            ->all();

        if ($jobs === []) {
            return;
        }

        Bus::batch($jobs)
            ->allowFailures()
            ->dispatch();
    }
}
