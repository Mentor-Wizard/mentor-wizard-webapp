<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * Class UserProfileResource
 *
 * @property string $name
 * @property string $last_name
 * @property string $linkedin
 * @property string $telegram
 * @property string $whatsapp
 * @property string $phone
 * @property string $description
 * @property string $avatar
 */
class UserProfileResource extends JsonResource
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
            'name'        => $this->name,
            'last_name'   => $this->last_name,
            'linkedin'    => $this->linkedin,
            'telegram'    => $this->telegram,
            'whatsapp'    => $this->whatsapp,
            'phone'       => $this->phone,
            'description' => $this->description,
            'avatar'      => $this->avatar,
        ];
    }
}
