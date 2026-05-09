<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\CalendarSyncStatusEnum;
use App\Models\CalendarEvent;
use App\Models\ExternalCalendarEvent;
use App\Models\UserCalendarIntegration;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessUpdateExternalCalendarEvent implements ShouldQueue
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

        if ($externalEvents->isEmpty()) {
            return;
        }

        $integrations = UserCalendarIntegration::query()
            ->whereIn('user_id', $externalEvents->pluck('user_id')->unique())
            ->where('sync_status', CalendarSyncStatusEnum::ACTIVE)
            ->get()
            ->keyBy(fn (UserCalendarIntegration $integration): string => $integration->user_id.'_'.$integration->provider->value);

        foreach ($externalEvents as $externalEvent) {
            /** @var UserCalendarIntegration|null $integration */
            $integration = $integrations->get($externalEvent->user_id.'_'.$externalEvent->provider->value);

            if ($integration === null) {
                continue;
            }

            dispatch(new UpdateExternalCalendarEvent($this->calendarEvent, $externalEvent, $integration));
        }
    }
}
