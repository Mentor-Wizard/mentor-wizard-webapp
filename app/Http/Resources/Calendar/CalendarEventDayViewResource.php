<?php

declare(strict_types=1);

namespace App\Http\Resources\Calendar;

use App\Enums\CalendarEventColoursEnum;
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
 * @property-read int $duration /
 */
class CalendarEventDayViewResource extends JsonResource
{
    public function __construct(mixed $resource, private ?string $timezone = null)
    {
        parent::__construct($resource);
        $this->timezone = $timezone ?? config('app.timezone');
    }

    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        $date = Date::parse($this->start_date_time)->setTimezone($this->timezone);
        $timezoneAbbreviation = $date->format('T');
        $dateTime = $date->format('Y-m-d').'"'.$timezoneAbbreviation.'"'.$date->format('H:i:s');

        $secondsSinceMidnight = ((int) $date->format('H')) * 3600
            + ((int) $date->format('i')) * 60
            + ((int) $date->format('s'));

        $userPivot = $this->calendarEventUsers
            ->firstWhere('id', auth()->user()?->getKey())
            ?->pivot;

        return [
            'id'            => $this->resource->getKey(),
            'time'          => $date->format('g:i A'),
            'dateTime'      => $dateTime,
            'durationIndex' => (int) ($this->duration * 12 / 3600),
            'startIndex'    => (int) (($secondsSinceMidnight * 6 / 3600) + 2),
            'title'         => $this->title,
            'href'          => $this->web_link,
            'colour'        => $userPivot?->colour ?? CalendarEventColoursEnum::randomValue(),
        ];
    }
}
