<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\ChatMessage;
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
 * @property ChatMessage $resource
 */
class ChatMessageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        $files = ChatFileResource::collection(
            $this->resource->getMedia('files')
        );

        return [
            'id'            => $this->resource->getKey(),
            'sender'        => $request->user()->getKey() === $this->resource->user_id ? 'user' : 'other',
            'avatar'        => $this->resource->user->profile->avatar,
            'timestamp'     => $this->resource->created_at,
            'content'       => strip_tags((string) $this->resource->message, '<b><i><em><strong><u>'),
            'isRead'        => $this->resource->is_read,
            'attachments'   => $files,
        ];
    }
}
