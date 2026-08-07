<?php

declare(strict_types=1);

namespace Modules\UserSchedule\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * @property-read int|string $id
 * @property mixed $user_id
 * @property mixed $type
 * @property mixed $end_time
 * @property mixed $start_time
 * @property mixed $day_of_week
 * @property mixed $day_off_date
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
        ];
    }
}
