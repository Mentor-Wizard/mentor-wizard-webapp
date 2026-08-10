<?php

declare(strict_types=1);

namespace Modules\ExternalCalendar\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Modules\ExternalCalendar\Models\ExternalCalendarEvent;
use Modules\ExternalCalendar\Models\UserCalendarIntegration;

class ProcessDeleteExternalCalendarEvent implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(public readonly int $calendarEventId) {}

    public function handle(): void
    {
        $externalEvents = ExternalCalendarEvent::query()
            ->where('calendar_event_id', $this->calendarEventId)
            ->get();

        if ($externalEvents->isEmpty()) {
            return;
        }

        $integrations = UserCalendarIntegration::query()
            ->whereIn('user_id', $externalEvents->pluck('user_id')->unique())
            ->get()
            ->keyBy(fn (UserCalendarIntegration $integration): string => $integration->user_id.'_'.$integration->provider->value);

        foreach ($externalEvents as $externalEvent) {
            /** @var UserCalendarIntegration|null $integration */
            $integration = $integrations->get($externalEvent->user_id.'_'.$externalEvent->provider->value);

            dispatch(new DeleteExternalCalendarEvent($externalEvent, $integration));
        }
    }
}
