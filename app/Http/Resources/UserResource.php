<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'created_at' => $this->created_at,
            'name' => $this->profile->name,
            'last_name' => $this->profile->last_name,
            'linkedin' => $this->profile->linkedin,
            'telegram' => $this->profile->telegram,
            'whatsapp' => $this->profile->whatsapp,
            'phone' => $this->profile->phone,
            'description' => $this->profile->description,
            'avatar' => $this->profile->avatar,
            'rating' => floor($this->rating * 10) / 10,
        ];
    }
}
