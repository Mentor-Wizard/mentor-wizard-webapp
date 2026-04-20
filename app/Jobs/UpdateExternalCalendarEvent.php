<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\ExternalCalendarEventLogTypeEnum;
use App\Enums\ExternalCalendarEventSyncStatusEnum;
use App\Models\CalendarEvent;
use App\Models\ExternalCalendarEvent;
use App\Models\ExternalCalendarEventLog;
use App\Models\UserCalendarIntegration;
use App\Services\ExternalCalendar\ExternalCalendarServiceFactory;
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

    public int $tries = 2;

    public int $backoff = 60;

    public function __construct(
        public readonly CalendarEvent $calendarEvent,
        public readonly ExternalCalendarEvent $externalEvent,
        public readonly UserCalendarIntegration $integration,
    ) {}

    public function handle(ExternalCalendarServiceFactory $factory): void
    {
        $service = $factory->for($this->integration->provider);

        $service->updateEvent($this->calendarEvent, $this->integration, $this->externalEvent->external_event_id);

        $this->externalEvent->update(['sync_status' => ExternalCalendarEventSyncStatusEnum::Synced]);

        ExternalCalendarEventLog::query()->create([
            'external_calendar_event_id' => $this->externalEvent->getKey(),
            'calendar_event_id'          => $this->calendarEvent->getKey(),
            'user_id'                    => $this->externalEvent->user_id,
            'provider'                   => $this->externalEvent->provider,
            'type'                       => ExternalCalendarEventLogTypeEnum::Success,
            'message'                    => sprintf('Event successfully updated in %s.',
                $this->externalEvent->provider->value),
        ]);
    }

    public function failed(Throwable $throwable): void
    {

        Log::error(sprintf('Failed to update external event %s for integration %s: %s',
            $this->externalEvent->external_event_id, $this->integration->getKey(), $throwable->getMessage()));

        $this->externalEvent->update(['sync_status' => ExternalCalendarEventSyncStatusEnum::Error]);

        ExternalCalendarEventLog::query()->create([
            'external_calendar_event_id' => $this->externalEvent->getKey(),
            'calendar_event_id'          => $this->calendarEvent->getKey(),
            'user_id'                    => $this->externalEvent->user_id,
            'provider'                   => $this->externalEvent->provider,
            'type'                       => ExternalCalendarEventLogTypeEnum::Error,
            'message'                    => $throwable->getMessage(),
        ]);
    }
}
