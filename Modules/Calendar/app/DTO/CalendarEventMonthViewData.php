<?php

declare(strict_types=1);

namespace Modules\Calendar\DTO;

use Modules\Calendar\Models\CalendarEvent;

final readonly class CalendarEventMonthViewData
{
    public function __construct(
        public int|string $id,
        public string $name,
        public string $time,
        public string $datetime,
        public ?string $webLink,
    ) {}

    public static function fromModel(CalendarEvent $event, string $timezone): self
    {
        return new self(
            id: $event->getKey(),
            name: $event->title,
            time: $event->start_date_time->timezone($timezone)->format('gA'),
            datetime: $event->start_date_time->timezone($timezone)->format('Y-m-d\TH:i'),
            webLink: $event->web_link,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'time'        => $this->time,
            'datetime'    => $this->datetime,
            'webLink'     => $this->webLink,
        ];
    }
}
