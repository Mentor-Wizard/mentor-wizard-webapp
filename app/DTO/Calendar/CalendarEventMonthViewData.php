<?php

declare(strict_types=1);

namespace App\DTO\Calendar;

use App\Models\CalendarEvent;

final readonly class CalendarEventMonthViewData
{
    public function __construct(
        public int|string $id,
        public string $name,
        public string $time,
        public string $datetime,
        public string $href,
    ) {}

    public static function fromModel(CalendarEvent $event, string $timezone): self
    {
        return new self(
            id: $event->getKey(),
            name: $event->title,
            time: $event->start_date_time->setTimezone($timezone)->format('gA'),
            datetime: $event->start_date_time->setTimezone($timezone)->format('Y-m-d\TH:i'),
            href: $event->web_link,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id'       => $this->id,
            'name'     => $this->name,
            'time'     => $this->time,
            'datetime' => $this->datetime,
            'href'     => $this->href,
        ];
    }
}
