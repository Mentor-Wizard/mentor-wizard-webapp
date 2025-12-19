<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

class SimilarMentorResource extends JsonResource
{
    public static $wrap;

    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        $this->resource->load('mentorProfile.currency');

        return [
            'id'       => $this->resource->id,
            'name'     => mb_trim($this->resource->profile->name.' '.$this->resource->profile->last_name),
            'avatar'   => $this->resource->profile->avatar,
            'title'    => $this->resource->mentorProfile?->title,
            'rate'     => $this->resource->mentorProfile?->rate,
            'currency' => $this->resource->mentorProfile?->currency->symbol,
            'rating'   => round($this->resource->rating, 1),
            'reviews'  => $this->resource->mentorReviews()->count(),
            'slug'     => $this->resource->slug,
        ];
    }
}
