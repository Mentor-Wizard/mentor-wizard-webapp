<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

class EventResource extends JsonResource
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
            'id'       => $this->unique_id,
        ];
    }
}
