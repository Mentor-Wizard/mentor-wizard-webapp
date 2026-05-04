<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\ExternalCalendarEventLogTypeEnum;
use App\Enums\ExternalCalendarEventSyncStatusEnum;
use App\Models\ExternalCalendarEvent;
use App\Models\ExternalCalendarEventLog;
use App\Models\UserCalendarIntegration;
use App\Services\ExternalCalendar\ExternalCalendarServiceFactory;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class DeleteExternalCalendarEvent implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $backoff = 60;

    public function __construct(
        public readonly ExternalCalendarEvent $externalEvent,
        public readonly ?UserCalendarIntegration $integration,
    ) {}

    public function handle(ExternalCalendarServiceFactory $factory): void
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

        $service = $factory->for($this->integration->provider);

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
    }

    public function failed(Throwable $throwable): void
    {
        Log::error(sprintf(
            'Failed to delete external event %s for integration %s: %s',
            $this->externalEvent->external_event_id,
            $this->integration?->getKey() ?? 'N/A',
            $throwable->getMessage()
        ));

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
