<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Date;
use Override;

/**
 * @property-read int|string $id
 * @property-read string $title
 * @property-read string $web_link
 * @property-read CarbonInterface $start_date_time
 * @property-read int $duration // in seconds
 */
class EventWeekViewResource extends JsonResource
{
    public function __construct(mixed $resource, private readonly ?string $timezone)
    {
        parent::__construct($resource);
    }

    #[Override]
    public function toArray(Request $request): array
    {
        $user = $this->additional['user'] ?? null;

        $date = Date::parse($this->start_date_time)->setTimezone($this->timezone);

        $timezoneAbbreviation = $date->format('T');
        $dateTime = $date->format('Y-m-d').'"'.$timezoneAbbreviation.'"'.$date->format('H:i:s');

        $secondsSinceMidnight = ((int) $date->format('H')) * 3600
            + ((int) $date->format('i')) * 60
            + ((int) $date->format('s'));

        return [
            'id'            => $this->resource->getKey(),
            'dayNumber'     => (int) $date->format('w') + 1,
            'time'          => $date->format('g:i A'),
            'dateTime'      => $dateTime,
            'durationIndex' => (int) ($this->duration * 12 / 3600),
            'startIndex'    => (int) (($secondsSinceMidnight * 6 / 3600) + 2),
            'title'         => $this->title,
            'href'          => $this->web_link,
            'colour'        => $this->resource->calendarEventUsers?->where('id', '=', $user->getKey())?->first()?->pivot?->colour,
        ];
    }
}
