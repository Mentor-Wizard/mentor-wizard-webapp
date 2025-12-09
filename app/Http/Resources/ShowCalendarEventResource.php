<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * @property-read int|string $id
 * @property-read string $title
 * @property-read CarbonInterface $start_date_time
 * @property-read CarbonInterface $end_date_time
 * @property-read string $type
 * @property-read int $duration
 * @property-read string $web_link
 * @property-read string $description
 */
class ShowCalendarEventResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        $user = auth()->user();
        $timezone = $user?->profile?->timezone ?? config('app.timezone');
        $startDateTime = $this->start_date_time->copy()->tz($timezone);
        $endDateTime = $this->end_date_time->copy()->tz($timezone);

        $userPivot = $this->calendarEventUsers
            ->firstWhere('id', $user?->getKey())
            ?->pivot;

        return [
            'id'                => $this->resource->getKey(),
            'title'             => $this->title,
            'fromDateFormatted' => $startDateTime->format('Y-M-d'),
            'fromDate'          => $startDateTime->format('Y-m-d'),
            'fromTime'          => $startDateTime->format('H:i'),
            'toDate'            => $endDateTime->format('Y-m-d'),
            'type'              => $this->type,
            'toDateFormatted'   => $endDateTime->format('Y-M-d'),
            'toTime'            => $endDateTime->format('H:i'),
            'duration'          => $startDateTime->diffInMinutes($endDateTime), // TODO: remove duration in DB
            'href'              => $this->web_link,
            'description'       => $this->description,
            'colour'            => $userPivot?->colour,
        ];
    }
}
