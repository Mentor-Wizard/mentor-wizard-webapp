<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class UserResource extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection->map(function ($user) {
                return [
                    'id' => $user->id,
                    'username' => $user->username,
                    'email' => $user->email,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                    'profile' =>$user->profile ? [
                        'id' => $user->profile->id,
                        'name' => $user->profile->name,
                        'last_name' => $user->profile->last_name,
                        'linkedin' => $user->profile->linkedin,
                        'telegram' => $user->profile->telegram,
                        'whatsapp' => $user->profile->whatsapp,
                        'phone' => $user->profile->phone,
                        'description' => $user->profile->description,
                        'title' => $user->profile->title,
                        'avatar' => $user->profile->getFirstMediaUrl('avatar') ?: null,
                    ]:null,
                ];
            }),

            'meta'
        ];
    }
}
