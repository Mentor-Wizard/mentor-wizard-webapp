<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class UserResource
 *
 * @package App\Http\Resources
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
    #[\Override]
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'username'    => $this->username,
            'email'       => $this->email,
            'created_at'  => $this->created_at,
            'name'        => $this->profile->name,
            'last_name'   => $this->profile->last_name,
            'linkedin'    => $this->profile->linkedin,
            'telegram'    => $this->profile->telegram,
            'whatsapp'    => $this->profile->whatsapp,
            'phone'       => $this->profile->phone,
            'description' => $this->profile->description,
            'avatar'      => $this->profile->avatar,
            'rating'      => floor($this->rating * 10) / 10,
        ];
    }
}
