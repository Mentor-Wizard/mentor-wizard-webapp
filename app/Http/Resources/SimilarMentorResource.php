<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\UserProfile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

class SimilarMentorResource extends JsonResource
{
    public static $wrap;

    #[Override]
    public function toArray(Request $request): array
    {
        //$this->resource->load('profile', 'mentorProfile');

        return [
            'id'       => $this->resource->id,
            'name'     => trim($this->resource->profile->name.' '.$this->resource->profile->last_name),
            'avatar' => $this->resource->profile->avatar ?? UserProfile::DEFAULT_AVATAR_URL,
            'title'    => $this->resource->mentorProfile?->title,
            'rate'     => $this->resource->mentorProfile?->rate,
            'currency' => $this->resource->mentorProfile?->currency->symbol,
            'rating'   => round($this->resource->rating, 1),
            'reviews'  => $this->resource->mentorReviews()->count(),
            'slug'     => $this->resource->slug,
        ];
    }
}
