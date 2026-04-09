<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\CalendarSyncStatusEnum;
use App\Models\CalendarEvent;
use App\Models\ExternalCalendarEvent;
use App\Models\UserCalendarIntegration;
use App\Services\ExternalCalendar\ExternalCalendarServiceInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class SyncCalendarEventToExternalCalendar implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(public readonly CalendarEvent $calendarEvent) {}

    public function handle(): void
    {
        $userIds = $this->calendarEvent
            ->calendarEventUsers()
            ->pluck('users.id');

        $integrations = UserCalendarIntegration::query()
            ->whereIn('user_id', $userIds)
            ->where('sync_status', CalendarSyncStatusEnum::Active)
            ->get();

        foreach ($integrations as $integration) {
            $this->syncForIntegration($integration);
        }
    }

    private function syncForIntegration(UserCalendarIntegration $integration): void
    {
        try {
            /** @var ExternalCalendarServiceInterface $service */
            $service = app($integration->provider->getService());

            $externalEventId = $service->createEvent($this->calendarEvent, $integration);

            ExternalCalendarEvent::query()->updateOrCreate(
                [
                    'calendar_event_id' => $this->calendarEvent->getKey(),
                    'user_id'           => $integration->user_id,
                    'provider'          => $integration->provider,
                ],
                [
                    'external_event_id' => $externalEventId,
                ]
            );
        } catch (Throwable $e) {
            $integration->update([
                'sync_status'        => CalendarSyncStatusEnum::Error,
                'last_error_message' => $e->getMessage(),
            ]);
        }
    }
}
