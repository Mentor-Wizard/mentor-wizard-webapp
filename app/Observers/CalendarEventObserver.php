<?php

declare(strict_types=1);

namespace App\Observers;

use App\Actions\Calendar\CreateMentorSessionForCalendarEvent;
use App\Enums\CalendarEventStatusEnum;
use App\Jobs\DeleteExternalCalendarEvent;
use App\Jobs\SyncCalendarEventToExternalCalendar;
use App\Jobs\UpdateExternalCalendarEvent;
use App\Models\CalendarEvent;
use Illuminate\Support\Facades\Log;

class CalendarEventObserver
{
    /** @var list<string> Fields whose changes should trigger an external calendar update. */
    private const array CONTENT_FIELDS = [
        'title',
        'description',
        'start_date_time',
        'end_date_time',
        'web_link',
    ];

    public function created(CalendarEvent $event): void
    {
        Log::info('Calendar event created'.$event->getKey().var_export($event->status, true));

        if ($event->status === CalendarEventStatusEnum::CONFIRMED) {
            SyncCalendarEventToExternalCalendar::dispatch($event);
        }
    }

    public function updated(CalendarEvent $event): void
    {
        Log::info('Calendar event updated'.$event->getKey().var_export($event->status, true));

        if ($event->wasChanged('status')) {
            (new CreateMentorSessionForCalendarEvent)->handle($event);

            if ($event->status === CalendarEventStatusEnum::CONFIRMED) {
                SyncCalendarEventToExternalCalendar::dispatch($event);
            }

            if ($event->status === CalendarEventStatusEnum::CANCELLED) {
                DeleteExternalCalendarEvent::dispatch($event->getKey());
            }
        } elseif ($event->status === CalendarEventStatusEnum::CONFIRMED && $event->wasChanged(self::CONTENT_FIELDS)) {
            UpdateExternalCalendarEvent::dispatch($event);
        }
    }

    public function deleting(CalendarEvent $event): void
    {
        DeleteExternalCalendarEvent::dispatch($event->getKey());
    }
}
