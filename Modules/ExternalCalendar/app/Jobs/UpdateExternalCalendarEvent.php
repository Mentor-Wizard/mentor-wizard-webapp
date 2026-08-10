<?php

declare(strict_types=1);

namespace Modules\ExternalCalendar\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Modules\Calendar\Models\CalendarEvent;
use Modules\ExternalCalendar\Enums\ExternalCalendarEventLogTypeEnum;
use Modules\ExternalCalendar\Enums\ExternalCalendarEventSyncStatusEnum;
use Modules\ExternalCalendar\Models\ExternalCalendarEvent;
use Modules\ExternalCalendar\Models\ExternalCalendarEventLog;
use Modules\ExternalCalendar\Models\UserCalendarIntegration;
use Modules\ExternalCalendar\Services\ExternalCalendarServiceFactory;
use Throwable;

class UpdateExternalCalendarEvent implements ShouldQueue
{
    use Queueable;

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
