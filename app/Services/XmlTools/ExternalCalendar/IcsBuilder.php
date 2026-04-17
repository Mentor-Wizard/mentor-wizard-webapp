<?php

declare(strict_types=1);

namespace App\Services\XmlTools\ExternalCalendar;

use App\Models\CalendarEvent;

class IcsBuilder
{
    public function build(string $uid, CalendarEvent $event): string
    {
        $now = now()->format('Ymd\THis\Z');
        $start = $event->start_date_time->timezone(config('app.timezone'))->format('Ymd\THis\Z');
        $end = $event->end_date_time->timezone(config('app.timezone'))->format('Ymd\THis\Z');

        $description = $event->description !== null
            ? 'DESCRIPTION:'.str_replace(["\r\n", "\n", "\r"], '\\n', $event->description)."\r\n"
            : '';

        return "BEGIN:VCALENDAR\r\n"
            ."VERSION:2.0\r\n"
            ."PRODID:-//MentorWizard//EN\r\n"
            ."BEGIN:VEVENT\r\n"
            ."UID:{$uid}\r\n"
            ."DTSTAMP:{$now}\r\n"
            ."DTSTART:{$start}\r\n"
            ."DTEND:{$end}\r\n"
            ."SUMMARY:{$event->title}\r\n"
            .$description
            ."END:VEVENT\r\n"
            ."END:VCALENDAR\r\n";
    }
}
