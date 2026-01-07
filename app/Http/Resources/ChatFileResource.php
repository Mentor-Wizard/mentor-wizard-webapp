<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

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
class ChatFileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        /** @var Media $media */
        $media = $this->resource;

        return [
            'id'         => $media->id,
            'name'       => $media->file_name,
            'mime_type'  => $media->mime_type,
            'size'       => $media->size,
            'created_at' => $media->created_at,
            'url'        => $media->getUrl(),
        ];
    }
}
