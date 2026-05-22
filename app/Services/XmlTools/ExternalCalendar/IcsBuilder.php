<?php

declare(strict_types=1);

namespace App\Services\XmlTools\ExternalCalendar;

use App\Models\CalendarEvent;
use App\Traits\ExternalCalendar\EscapesText;
use Date;

class IcsBuilder
{
    use EscapesText;

    public function build(string $uid, CalendarEvent $event): string
    {
        $now = Date::now()->format('Ymd\THis\Z');
        $start = $event->start_date_time->timezone(config('app.timezone'))->format('Ymd\THis\Z');
        $end = $event->end_date_time->timezone(config('app.timezone'))->format('Ymd\THis\Z');

        $description = $event->description !== null
            ? 'DESCRIPTION:'.$this->escapeText($event->description)."\r\n"
            : '';

        $summary = $this->escapeText($event->title);

        return "BEGIN:VCALENDAR\r\n"
            ."VERSION:2.0\r\n"
            ."PRODID:-//MentorWizard//EN\r\n"
            ."BEGIN:VEVENT\r\n"
            ."UID:{$uid}\r\n"
            ."DTSTAMP:{$now}\r\n"
            ."DTSTART:{$start}\r\n"
            ."DTEND:{$end}\r\n"
            ."SUMMARY:{$summary}\r\n"
            .$description
            ."END:VEVENT\r\n"
            ."END:VCALENDAR\r\n";
    }
}
