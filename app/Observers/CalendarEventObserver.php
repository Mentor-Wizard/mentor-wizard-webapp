<?php

declare(strict_types=1);

namespace App\Observers;

use App\Actions\Calendar\CreateMentorSessionForCalendarEvent;
use App\Enums\CalendarEventStatusEnum;
use App\Jobs\DeleteExternalCalendarEvent;
use App\Jobs\SyncCalendarEventToExternalCalendar;
use App\Jobs\UpdateExternalCalendarEvent;
use App\Models\CalendarEvent;

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
        if ($event->status === CalendarEventStatusEnum::CONFIRMED) {
            dispatch(new SyncCalendarEventToExternalCalendar($event));
        }
    }

    public function updated(CalendarEvent $event): void
    {
        if ($event->wasChanged('status')) {
            (new CreateMentorSessionForCalendarEvent)->handle($event);

            if ($event->status === CalendarEventStatusEnum::CONFIRMED) {
                dispatch(new SyncCalendarEventToExternalCalendar($event));
            }

            if ($event->status === CalendarEventStatusEnum::CANCELLED) {
                dispatch(new DeleteExternalCalendarEvent($event->getKey()));
            }
        } elseif ($event->status === CalendarEventStatusEnum::CONFIRMED && $event->wasChanged(self::CONTENT_FIELDS)) {
            dispatch(new UpdateExternalCalendarEvent($event));
        }
    }

    public function deleting(CalendarEvent $event): void
    {
        dispatch(new DeleteExternalCalendarEvent($event->getKey()));
    }
}
