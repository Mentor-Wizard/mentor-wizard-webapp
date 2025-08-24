<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Carbon\CarbonInterval;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * @property-read int|string $id
 * @property-read string $title
 * @property-read \Illuminate\Support\Carbon $start_date_time
 * @property-read \Illuminate\Support\Carbon $end_date_time
 * @property-read string $type
 * @property-read int $duration
 * @property-read string $web_link
 * @property-read string $description
 */
class EventShowResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'title'             => $this->title,
            'fromDateFormatted' => $this->start_date_time->format('Y-M-d'),
            'fromDate'          => $this->start_date_time->format('Y-m-d'),
            'fromTime'          => $this->start_date_time->format('H:i'),
            'toDate'            => $this->end_date_time->format('Y-m-d'),
            'type'              => $this->type,
            'toDateFormatted'   => $this->end_date_time->format('Y-M-d'),
            'toTime'            => $this->end_date_time->format('H:i'),
            'duration'          => CarbonInterval::seconds($this->duration)->cascade()->format('%H:%I'),
            'href'              => $this->web_link,
            'description'       => $this->description,
        ];
    }
}
