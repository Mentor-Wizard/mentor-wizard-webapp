<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;
use Override;

/**
 * @property-read int|string $id
 * @property-read string $title
 * @property-read Carbon $start_date_time
 * @property-read string $web_link
 */
class EventMonthViewResource extends JsonResource
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
            'name'     => $this->title,
            'time'     => $this->start_date_time->format('gA'),
            'datetime' => $this->start_date_time->format('Y-m-d\TH:i'),
            'href'     => $this->web_link,
            'id'       => $this->id,
        ];
    }
}
