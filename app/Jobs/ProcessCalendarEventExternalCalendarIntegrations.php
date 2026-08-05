<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\CalendarSyncStatusEnum;
use App\Models\UserCalendarIntegration;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Modules\Calendar\Models\CalendarEvent;

class ProcessCalendarEventExternalCalendarIntegrations implements ShouldQueue
{
    use Batchable;
    use Queueable;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(public readonly CalendarEvent $calendarEvent) {}

    public function handle(): void
    {
        $userIds = $this->calendarEvent
            ->calendarEventUsers()
            ->pluck('users.id');

        $integrations = UserCalendarIntegration::query()
            ->whereIn('user_id', $userIds)
            ->where('sync_status', CalendarSyncStatusEnum::ACTIVE)
            ->get();

        foreach ($integrations as $integration) {
            dispatch(new CreateExternalCalendarEvent($this->calendarEvent, $integration));
        }
    }
}
