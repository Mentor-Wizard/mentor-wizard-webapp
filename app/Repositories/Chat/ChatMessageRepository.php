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
    public function getMessages(Chat $chat): array
    {
        return ChatMessage::query()
            ->with('chat.owner.profile')
            ->whereIn('chat_id', [$chat->id, $chat->companion_chat_id])
            ->orderBy('id')
            ->get()
            ->map(fn ($message) => ChatMessageResource::make($message))
            ->all();
    }

    public function getFiles(Chat $chat): array
    {
        return ChatMessage::query()
            ->whereIn('chat_id', [$chat->id, $chat->companion_chat_id])
            ->whereHas('media', fn ($q) => $q->where('collection_name', 'files'))
            ->get()
            ->flatMap(fn ($message): MediaCollection => $message->getMedia('files'))
            ->map(fn ($media): array => new ChatFileResource($media)->toArray(request()))
            ->values()
            ->all();
    }
}
