<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\CalendarSyncStatusEnum;
use App\Models\ExternalCalendarEvent;
use App\Models\UserCalendarIntegration;
use App\Services\ExternalCalendar\ExternalCalendarServiceInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class DeleteExternalCalendarEvent implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(public readonly int $calendarEventId) {}

    public function handle(): void
    {
        $externalEvents = ExternalCalendarEvent::query()
            ->where('calendar_event_id', $this->calendarEventId)
            ->get();

        foreach ($externalEvents as $externalEvent) {
            $integration = UserCalendarIntegration::query()
                ->where('user_id', $externalEvent->user_id)
                ->where('provider', $externalEvent->provider)
                ->first();

            if ($integration === null) {
                $externalEvent->delete();

                continue;
            }

            $this->deleteForIntegration($integration, $externalEvent);
        }
    }

    private function deleteForIntegration(UserCalendarIntegration $integration, ExternalCalendarEvent $externalEvent): void
    {
        try {
            /** @var ExternalCalendarServiceInterface $service */
            $service = app($integration->provider->serviceClass());

            $service->deleteEvent($integration, $externalEvent->external_event_id);

            Log::info("Deleted external event {$externalEvent->external_event_id} for calendar event {$this->calendarEventId}");

            $externalEvent->delete();
        } catch (Throwable $e) {
            Log::error("Failed to delete calendar event {$this->calendarEventId} for integration {$integration->getKey()}: {$e->getMessage()}");

            $integration->update([
                'sync_status'        => CalendarSyncStatusEnum::Error,
                'last_error_message' => $e->getMessage(),
            ]);
        }
    }
}
