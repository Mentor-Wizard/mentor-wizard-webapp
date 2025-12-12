<?php

declare(strict_types=1);

namespace App\DTO\Calendar;

use App\Models\CalendarEvent;
use App\Models\User;
use App\Traits\Calendar\CalculatesCalendarMetrics;
use App\Traits\Calendar\RetrievesUserPivotData;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Facades\Date;

final readonly class CalendarEventWeekViewData
{
    use CalculatesCalendarMetrics;
    use RetrievesUserPivotData;

    public function __construct(
        public int|string $id,
        public int $dayNumber,
        public string $time,
        public string $dateTime,
        public int $durationIndex,
        public int $startIndex,
        public string $title,
        public ?string $webLink,
        public ?string $colour,
    ) {}

    public static function fromModel(CalendarEvent $event, string $timezone, ?User $user = null): self
    {
        $user ??= auth()->user();

        $date = Date::parse($event->start_date_time)->setTimezone($timezone);

        $dateTime = self::formatDateTimeWithTimezone($date);
        $secondsSinceMidnight = self::calculateSecondsSinceMidnight($date);
        $userPivot = $event->calendarEventUsers
            ->firstWhere('id', $user?->getKey())
            ?->pivot;

        /** @var Pivot|null $userPivot */
        return new self(
            id: $event->getKey(),
            dayNumber: (int) $date->format('w') + 1,
            time: $date->format('g:i A'),
            dateTime: $dateTime,
            durationIndex: self::calculateDurationIndex($event->duration),
            startIndex: self::calculateStartIndex($secondsSinceMidnight),
            title: $event->title,
            webLink: $event->web_link,
            colour: $userPivot?->getAttribute('colour'),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id'            => $this->id,
            'dayNumber'     => $this->dayNumber,
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
