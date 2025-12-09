<?php

declare(strict_types=1);

namespace App\Http\Resources\Calendar;

use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * @property-read int|string $id
 * @property-read string $title
 * @property-read CarbonInterface $start_date_time
 * @property-read string $web_link
 */
class CalendarEventMonthViewResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        $timezone = $this->additional['timeZone'] ?? 'UTC';

        return [
            'name'     => $this->title,
            'time'     => $this->start_date_time->setTimezone($timezone)->format('gA'),
            'datetime' => $this->start_date_time->setTimezone($timezone)->format('Y-m-d\TH:i'),
            'href'     => $this->web_link,
            'id'       => $this->resource->getKey(),
        ];
    }
}
