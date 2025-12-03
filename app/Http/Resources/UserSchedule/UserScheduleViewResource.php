<?php

declare(strict_types=1);

namespace App\Http\Resources\UserSchedule;

use Carbon\CarbonInterface;
use Carbon\CarbonInterval;
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
class UserScheduleViewResource extends JsonResource
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
            'id'                => $this->resource->getKey(),
            'user_id'           => $this->user_id,
            'day_of_week'       => $this->day_of_week,
            'start_time'        => $this->start_time,
            'end_time'          => $this->end_time,
            'type'              => $this->type,
            'day_off_date'      => $this->day_off_date?->format('Y-m-d'),
            'timezone'          => $this->timezone
        ];
    }
}
