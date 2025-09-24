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
            'name'        => $this->resource->name,
            'last_name'   => $this->resource->last_name,
            'linkedin'    => $this->resource->linkedin,
            'telegram'    => $this->resource->telegram,
            'whatsapp'    => $this->resource->whatsapp,
            'phone'       => $this->resource->phone,
            'avatar'      => $this->resource->avatar,
        ];
    }
}
