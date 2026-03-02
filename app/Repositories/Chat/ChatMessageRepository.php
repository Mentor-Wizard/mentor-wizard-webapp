<?php

declare(strict_types=1);

namespace App\Repositories\Chat;

use App\Http\Resources\ChatFileResource;
use App\Http\Resources\ChatMessageResource;
use App\Models\Chat;
use App\Models\ChatMessage;
use Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection;

class ChatMessageRepository
{
    /**
     * @return ChatMessageResource[]
     */
    public function getMessages(Chat $chat): array
    {
        return ChatMessage::query()
            ->with('user.profile', 'chat')
            ->where('chat_id', $chat->id)
            ->orderBy('id')
            ->get()
            ->map(fn ($message) => ChatMessageResource::make($message))
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getFiles(Chat $chat): array
    {
        return ChatMessage::query()
            ->where('chat_id', $chat->id)
            ->whereHas('media', fn ($q) => $q->where('collection_name', 'files'))
            ->get()
            ->flatMap(fn ($message): MediaCollection => $message->getMedia('files'))
            ->map(fn ($media): array => new ChatFileResource($media)->toArray(request()))
            ->values()
            ->all();
    }
}
