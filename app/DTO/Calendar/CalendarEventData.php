<?php

declare(strict_types=1);

namespace App\DTO\Calendar;

use App\Models\CalendarEvent;
use App\Models\User;
use App\Traits\Calendar\RetrievesUserPivotData;
use Illuminate\Database\Eloquent\Relations\Pivot;

final readonly class CalendarEventData
{
    use RetrievesUserPivotData;

    public function __construct(
        public int|string $id,
        public string $title,
        public string $fromDateFormatted,
        public string $fromDate,
        public string $fromTime,
        public string $toDate,
        public string $type,
        public string $toDateFormatted,
        public string $toTime,
        public int $duration,
        public ?string $webLink,
        public ?string $description,
        public ?string $colour,
    ) {}

    public static function fromModel(CalendarEvent $event, string $timezone, ?User $user = null): self
    {
        $user ??= auth()->user();

        $startDateTime = $event->start_date_time->copy()->tz($timezone);
        $endDateTime = $event->end_date_time->copy()->tz($timezone);

        $userPivot = $event->calendarEventUsers
            ->firstWhere('id', $user?->getKey())
            ?->pivot;

        /** @var Pivot|null $userPivot */
        return new self(
            id: $event->getKey(),
            title: $event->title,
            fromDateFormatted: $startDateTime->format('Y-M-d'),
            fromDate: $startDateTime->format('Y-m-d'),
            fromTime: $startDateTime->format('H:i'),
            toDate: $endDateTime->format('Y-m-d'),
            type: $event->type,
            toDateFormatted: $endDateTime->format('Y-M-d'),
            toTime: $endDateTime->format('H:i'),
            duration: (int) $startDateTime->diffInMinutes($endDateTime),
            webLink: $event->web_link,
            description: $event->description,
            colour: $userPivot?->getAttribute('colour'),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id'                => $this->id,
            'title'             => $this->title,
            'fromDateFormatted' => $this->fromDateFormatted,
            'fromDate'          => $this->fromDate,
            'fromTime'          => $this->fromTime,
            'toDate'            => $this->toDate,
            'type'              => $this->type,
            'toDateFormatted'   => $this->toDateFormatted,
            'toTime'            => $this->toTime,
            'duration'          => $this->duration,
            'webLink'           => $this->webLink,
            'description'       => $this->description,
            'colour'            => $this->colour,
        ];
    }
}
