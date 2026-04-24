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
use Throwable;

class CreateExternalCalendarEvent implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

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
            'message'                    => 'Failed to create external calendar event.',
        ]);
    }
}
