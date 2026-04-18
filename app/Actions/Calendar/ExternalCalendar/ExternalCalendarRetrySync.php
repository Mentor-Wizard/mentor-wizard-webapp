<?php

declare(strict_types=1);

namespace App\Actions\Calendar\ExternalCalendar;

use App\Enums\CalendarEventStatusEnum;
use App\Enums\CalendarProviderEnum;
use App\Enums\CalendarSyncStatusEnum;
use App\Http\Requests\Calendar\ExternalCalendarRetrySyncRequest;
use App\Jobs\ProcessCalendarEventExternalCalendarIntegrations;
use App\Models\CalendarEvent;
use App\Models\ExternalCalendarEvent;
use App\Models\User;
use App\Models\UserCalendarIntegration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Date;
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

        Bus::batch($jobs)
            ->allowFailures()
            ->dispatch();
    }
}
