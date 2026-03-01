<?php

declare(strict_types=1);

namespace App\DTO\Calendar;

use App\Enums\CalendarEventColoursEnum;
use App\Models\CalendarEvent;
use App\Models\User;
use App\Traits\Calendar\CalculatesCalendarMetrics;
use App\Traits\Calendar\RetrievesUserPivotData;
use Illuminate\Support\Facades\Date;

final readonly class CalendarEventDayViewData
{
    use CalculatesCalendarMetrics;
    use RetrievesUserPivotData;

    public function __construct(
        public int|string $id,
        public string $time,
        public string $dateTime,
        public int $durationIndex,
        public int $startIndex,
        public string $title,
        public ?string $webLink,
        public string $colour,
    ) {}

    public static function fromModel(CalendarEvent $event, string $timezone, ?User $user = null): self
    {
        $user ??= auth()->user();

        $date = Date::parse($event->start_date_time)->timezone($timezone);
        $secondsSinceMidnight = self::calculateSecondsSinceMidnight($date);

        return new self(
            id: $event->getKey(),
            time: $date->format('g:i A'),
            dateTime: self::formatDateTimeWithTimezone($date),
            durationIndex: self::calculateDurationIndex($event->duration),
            startIndex: self::calculateStartIndex($secondsSinceMidnight),
            title: $event->title,
            webLink: $event->web_link,
            colour: self::getUserColour($event, $user) ?? CalendarEventColoursEnum::randomValue(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id'            => $this->id,
            'time'          => $this->time,
            'dateTime'      => $this->dateTime,
            'durationIndex' => $this->durationIndex,
            'startIndex'    => $this->startIndex,
            'title'         => $this->title,
            'webLink'       => $this->webLink,
            'colour'        => $this->colour,
        ];
    }
}
