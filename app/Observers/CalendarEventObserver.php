<?php

declare(strict_types=1);

namespace App\Observers;

use App\Actions\Calendar\CreateMentorSessionForCalendarEvent;
use App\Enums\CalendarEventStatusEnum;
use App\Jobs\ProcessCalendarEventExternalCalendarIntegrations;
use App\Jobs\ProcessDeleteExternalCalendarEvent;
use App\Jobs\ProcessUpdateExternalCalendarEvent;
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
            dispatch(new ProcessCalendarEventExternalCalendarIntegrations($event));
        }
    }

    public function updated(CalendarEvent $event): void
    {
        if ($event->wasChanged('status')) {
            (new CreateMentorSessionForCalendarEvent)->handle($event);

            if ($event->status === CalendarEventStatusEnum::CONFIRMED) {
                dispatch(new ProcessCalendarEventExternalCalendarIntegrations($event));
            }

            if ($event->status === CalendarEventStatusEnum::CANCELLED) {
                dispatch(new ProcessDeleteExternalCalendarEvent($event->getKey()));
            }
        } elseif ($event->status === CalendarEventStatusEnum::CONFIRMED && $event->wasChanged(self::CONTENT_FIELDS)) {
            dispatch(new ProcessUpdateExternalCalendarEvent($event));
        }
    }

    public function deleting(CalendarEvent $event): void
    {
        dispatch(new ProcessDeleteExternalCalendarEvent($event->getKey()));
    }
}
