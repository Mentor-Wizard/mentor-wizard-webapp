<?php

declare(strict_types=1);

namespace Modules\ExternalCalendar\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Modules\Calendar\Models\CalendarEvent;
use Modules\ExternalCalendar\Enums\ExternalCalendarEventLogTypeEnum;
use Modules\ExternalCalendar\Enums\ExternalCalendarEventSyncStatusEnum;
use Modules\ExternalCalendar\Models\ExternalCalendarEvent;
use Modules\ExternalCalendar\Models\ExternalCalendarEventLog;
use Modules\ExternalCalendar\Models\UserCalendarIntegration;
use Modules\ExternalCalendar\Services\ExternalCalendarServiceFactory;
use Throwable;

class CreateExternalCalendarEvent implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $backoff = 60;

    public function __construct(
        public readonly CalendarEvent $calendarEvent,
        public readonly UserCalendarIntegration $integration,
    ) {}

    public function handle(ExternalCalendarServiceFactory $factory): void
    {
        $service = $factory->for($this->integration->provider);

        $externalEventId = $service->createEvent($this->calendarEvent, $this->integration);

        $externalEvent = ExternalCalendarEvent::query()->updateOrCreate(
            [
                'calendar_event_id' => $this->calendarEvent->getKey(),
                'user_id'           => $this->integration->user_id,
                'provider'          => $this->integration->provider,
            ],
            [
                'external_event_id' => $externalEventId,
                'sync_status'       => ExternalCalendarEventSyncStatusEnum::Synced,
            ]
        );

        ExternalCalendarEventLog::query()->create([
            'external_calendar_event_id' => $externalEvent->getKey(),
            'calendar_event_id'          => $this->calendarEvent->getKey(),
            'user_id'                    => $this->integration->user_id,
            'provider'                   => $this->integration->provider,
            'type'                       => ExternalCalendarEventLogTypeEnum::Success,
            'message'                    => sprintf('Event successfully created in %s.', $this->integration->provider->value),
        ]);
    }

    public function failed(Throwable $throwable): void
    {
        $externalEvent = ExternalCalendarEvent::query()->updateOrCreate(
            [
                'calendar_event_id' => $this->calendarEvent->getKey(),
                'user_id'           => $this->integration->user_id,
                'provider'          => $this->integration->provider,
            ],
            [
                'sync_status' => ExternalCalendarEventSyncStatusEnum::Error,
            ]
        );

        ExternalCalendarEventLog::query()->create([
            'external_calendar_event_id' => $externalEvent->getKey(),
            'calendar_event_id'          => $this->calendarEvent->getKey(),
            'user_id'                    => $this->integration->user_id,
            'provider'                   => $this->integration->provider,
            'type'                       => ExternalCalendarEventLogTypeEnum::Error,
            'message'                    => $throwable->getMessage(),
        ]);
    }
}
