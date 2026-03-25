<?php

declare(strict_types=1);

namespace App\Observers;

use App\Actions\Calendar\CreateMentorSessionForCalendarEvent;
use App\Models\CalendarEvent;

class CalendarEventObserver
{
    public function updated(CalendarEvent $event): void
    {
        if (! $event->wasChanged('status')) {
            return;
        }

        (new CreateMentorSessionForCalendarEvent)->handle($event);
    }
}
