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
        /** @var ChatMessage $chatMessage */
        $chatMessage = $this->resource;
        $files = ChatFileResource::collection(
            $chatMessage->getMedia('files')
        );

        return [
            'id'            => $chatMessage->id,
            'sender'        => $request->user()->id === $chatMessage->chat->owner_id ? 'user' : 'other',
            'avatar'        => $chatMessage->chat->owner->profile->avatar,
            'timestamp'     => $chatMessage->created_at,
            'content'       => $chatMessage->message,
            'isRead'        => $chatMessage->is_read,
            'attachments'   => $files,
        ];
    }
}
