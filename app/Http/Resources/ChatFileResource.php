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
     *
     * @property Media $resource
     */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->resource->getKey(),
            'name'       => $this->resource->file_name,
            'mimeType'   => $this->resource->mime_type,
            'size'       => $this->resource->size,
            'createdAt'  => $this->resource->created_at,
            'url'        => route('chat.message.download', [
                'message' => $this->resource->model_id,
                'media'   => $this->resource->getKey(),
            ]),
        ];
    }
}
