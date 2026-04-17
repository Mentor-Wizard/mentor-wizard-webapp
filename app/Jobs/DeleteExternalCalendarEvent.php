<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\ExternalCalendarEventLogTypeEnum;
use App\Enums\ExternalCalendarEventSyncStatusEnum;
use App\Models\ExternalCalendarEvent;
use App\Models\ExternalCalendarEventLog;
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

    public function __construct(
        public readonly ExternalCalendarEvent $externalEvent,
        public readonly ?UserCalendarIntegration $integration,
    ) {}

    public function handle(): void
    {
        if (! $this->integration instanceof UserCalendarIntegration) {
            ExternalCalendarEventLog::query()->create([
                'external_calendar_event_id' => $this->externalEvent->getKey(),
                'calendar_event_id'          => $this->externalEvent->calendar_event_id,
                'user_id'                    => $this->externalEvent->user_id,
                'provider'                   => $this->externalEvent->provider,
                'type'                       => ExternalCalendarEventLogTypeEnum::Info,
                'message'                    => 'No active integration found. Local record removed.',
            ]);

            $this->externalEvent->delete();

            return;
        }

        try {
            /** @var ExternalCalendarServiceInterface $service */
            $service = resolve($this->integration->provider->getService());

            $service->deleteEvent($this->integration, $this->externalEvent->external_event_id);

            ExternalCalendarEventLog::query()->create([
                'external_calendar_event_id' => $this->externalEvent->getKey(),
                'calendar_event_id'          => $this->externalEvent->calendar_event_id,
                'user_id'                    => $this->externalEvent->user_id,
                'provider'                   => $this->externalEvent->provider,
                'type'                       => ExternalCalendarEventLogTypeEnum::Success,
                'message'                    => sprintf('Event successfully deleted from %s.', $this->externalEvent->provider->value),
            ]);

            $this->externalEvent->delete();
        } catch (Throwable $throwable) {
            Log::error(sprintf('Failed to delete external event %s for integration %s: %s', $this->externalEvent->external_event_id, $this->integration->getKey(), $throwable->getMessage()));

            $this->externalEvent->update(['sync_status' => ExternalCalendarEventSyncStatusEnum::Error]);

            ExternalCalendarEventLog::query()->create([
                'external_calendar_event_id' => $this->externalEvent->getKey(),
                'calendar_event_id'          => $this->externalEvent->calendar_event_id,
                'user_id'                    => $this->externalEvent->user_id,
                'provider'                   => $this->externalEvent->provider,
                'type'                       => ExternalCalendarEventLogTypeEnum::Error,
                'message'                    => $throwable->getMessage(),
            ]);
        }
    }
}
