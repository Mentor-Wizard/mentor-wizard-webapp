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

class SyncCalendarEventToProvider implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public readonly CalendarEvent $calendarEvent,
        public readonly UserCalendarIntegration $integration,
    ) {}

    public function handle(): void
    {
        try {
            /** @var ExternalCalendarServiceInterface $service */
            $service = resolve($this->integration->provider->getService());

            $externalEventId = $service->createEvent($this->calendarEvent, $this->integration);

            ExternalCalendarEvent::query()->updateOrCreate(
                [
                    'calendar_event_id' => $this->calendarEvent->getKey(),
                    'user_id'           => $this->integration->user_id,
                    'provider'          => $this->integration->provider,
                ],
                [
                    'external_event_id' => $externalEventId,
                ]
            );
        } catch (Throwable $throwable) {
            $this->integration->update([
                'sync_status'        => CalendarSyncStatusEnum::Error,
                'last_error_message' => $throwable->getMessage(),
            ]);
        }
    }
}
