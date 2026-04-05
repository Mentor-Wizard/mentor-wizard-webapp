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
use Illuminate\Support\Facades\Log;
use Throwable;

class UpdateExternalCalendarEvent implements ShouldQueue
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
        $externalEvents = ExternalCalendarEvent::query()
            ->where('calendar_event_id', $this->calendarEvent->getKey())
            ->get();

        foreach ($externalEvents as $externalEvent) {
            $integration = UserCalendarIntegration::query()
                ->where('user_id', $externalEvent->user_id)
                ->where('provider', $externalEvent->provider)
                ->where('sync_status', CalendarSyncStatusEnum::Active)
                ->first();

            if ($integration === null) {
                continue;
            }

            $this->updateForIntegration($integration, $externalEvent);
        }
    }

    private function updateForIntegration(UserCalendarIntegration $integration, ExternalCalendarEvent $externalEvent): void
    {
        try {
            /** @var ExternalCalendarServiceInterface $service */
            $service = app($integration->provider->serviceClass());

            $service->updateEvent($this->calendarEvent, $integration, $externalEvent->external_event_id);

            Log::info("Updated external event {$externalEvent->external_event_id} for calendar event {$this->calendarEvent->getKey()}");
        } catch (Throwable $e) {
            Log::error("Failed to update calendar event {$this->calendarEvent->getKey()} for integration {$integration->getKey()}: {$e->getMessage()}");

            $integration->update([
                'sync_status'        => CalendarSyncStatusEnum::Error,
                'last_error_message' => $e->getMessage(),
            ]);
        }
    }
}
