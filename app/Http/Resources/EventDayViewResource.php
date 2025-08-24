<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\EventCalendarColoursEnum;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * @property-read int|string $id
 * @property-read string $title
 * @property-read string $web_link
 * @property-read Carbon $start_date_time
 * @property-read int $duration /
 */
class EventDayViewResource extends JsonResource
{
    public function __construct(mixed $resource, private readonly ?string $timezone = null)
    {
        parent::__construct($resource);
    }

    #[Override]
    public function toArray(Request $request): array
    {
        $date = Carbon::parse($this->start_date_time);
        if ($this->timezone !== null && $this->timezone !== '' && $this->timezone !== '0') {
            $date = $date->clone()->setTimezone($this->timezone);
        }

        $timezoneAbbreviation = $date->format('T');
        $dateTime = $date->format('Y-m-d').'"'.$timezoneAbbreviation.'"'.$date->format('H:i:s');

        $secondsSinceMidnight = ((int) $date->format('H')) * 3600
            + ((int) $date->format('i')) * 60
            + ((int) $date->format('s'));

        return [
            'id'            => $this->id,
            'time'          => $date->format('g:i A'),
            'dateTime'      => $dateTime,
            'durationIndex' => (int) ($this->duration * 12 / 3600),
            'startIndex'    => (int) (($secondsSinceMidnight * 6 / 3600) + 2),
            'title'         => $this->title,
            'href'          => $this->web_link,
            'colour'        => EventCalendarColoursEnum::randomValue(),
        ];
    }
}
