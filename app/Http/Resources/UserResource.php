<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * Class UserResource
 *
 *
 * @property int $id
 * @property string $username
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property-read \App\Models\UserProfile $profile
 * @property float|null $rating
 */
class UserResource extends JsonResource
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
            'id'          => $this->id,
            'username'    => $this->username,
            'email'       => $this->email,
            'created_at'  => $this->created_at,
            'profile'     => UserProfileResource::make($this->profile)->resolve(),
            'rating'      => floor($this->rating * 10) / 10,
        ];
    }
}
