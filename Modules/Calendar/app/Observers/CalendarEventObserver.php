<?php

declare(strict_types=1);

namespace Modules\Calendar\Observers;

use Modules\Calendar\Enums\CalendarEventStatusEnum;
use Modules\Calendar\Events\CalendarEventCancelled;
use Modules\Calendar\Events\CalendarEventConfirmed;
use Modules\Calendar\Events\CalendarEventContentChanged;
use Modules\Calendar\Events\CalendarEventDeleting;
use Modules\Calendar\Models\CalendarEvent;
use Modules\MentorSession\Actions\CreateMentorSessionForCalendarEvent;

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
            event(new CalendarEventConfirmed($event));
        }
    }

    public function updated(CalendarEvent $event): void
    {
        if ($event->wasChanged('status')) {
            CreateMentorSessionForCalendarEvent::run($event);

            if ($event->status === CalendarEventStatusEnum::CONFIRMED) {
                event(new CalendarEventConfirmed($event));
            }

            if ($event->status === CalendarEventStatusEnum::CANCELLED) {
                event(new CalendarEventCancelled($event->getKey()));
            }
        } elseif ($event->status === CalendarEventStatusEnum::CONFIRMED && $event->wasChanged(self::CONTENT_FIELDS)) {
            event(new CalendarEventContentChanged($event));
        }
    }

    public function deleting(CalendarEvent $event): void
    {
        event(new CalendarEventDeleting($event->getKey()));
    }
}
