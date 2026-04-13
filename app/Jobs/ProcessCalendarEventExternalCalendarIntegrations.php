<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\CalendarSyncStatusEnum;
use App\Models\CalendarEvent;
use App\Models\UserCalendarIntegration;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessCalendarEventExternalCalendarIntegrations implements ShouldQueue
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
        $userIds = $this->calendarEvent
            ->calendarEventUsers()
            ->pluck('users.id');

        $integrations = UserCalendarIntegration::query()
            ->whereIn('user_id', $userIds)
            ->where('sync_status', CalendarSyncStatusEnum::Active)
            ->get();

        foreach ($integrations as $integration) {
            dispatch(new CreateExternalCalendarEvent($this->calendarEvent, $integration));
        }
    }
}
