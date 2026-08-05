<?php

declare(strict_types=1);

namespace Modules\Chat\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Chat\Models\ChatMessage;
use Override;
use Stevebauman\Purify\Facades\Purify;

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

        $currentUser = $request->user();

        return [
            'id'            => $this->resource->getKey(),
            'user_id'       => $this->resource->user_id,
            'sender'        => ($currentUser && $currentUser->getKey() === $this->resource->user_id) ? 'user' : 'other',
            'avatar'        => $this->resource->user->profile->avatar,
            'timestamp'     => $this->resource->created_at,
            'message'       => Purify::clean($this->resource->message ?? ''),
            'isRead'        => $this->resource->is_read,
            'attachments'   => $files,
        ];
    }
}
