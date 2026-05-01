<?php

declare(strict_types=1);

namespace App\Actions\Chat;

use App\Events\Chats\UnreadMessagesEvent;
use App\Http\Resources\ChatFileResource;
use App\Http\Resources\ChatMessageResource;
use App\Models\Chat;
use App\Models\ChatMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Lorisleiva\Actions\Concerns\AsController;
use Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection;

class ChatMessages
{
    use AsController;

    public function handle(Request $request, Chat $chat): JsonResponse
    {
        $user = $request->user();
        $this->setReadMessages($chat);
        event(new UnreadMessagesEvent($user, UnreadMessages::run($user)));

        return response()->json([
            'messages' => $this->getMessages($chat),
            'files'    => $this->getFiles($chat),
        ]);
    }

    private function setReadMessages(Chat $chat): void
    {
        ChatMessage::query()
            ->where('chat_id', $chat->getKey())
            ->update(['is_read' => true]);
    }

    /**
     * @return array<int, ChatMessageResource>
     */
    private function getMessages(Chat $chat): array
    {
        return ChatMessage::query()
            ->with('user.profile', 'chat')
            ->where('chat_id', $chat->getKey())
            ->orderBy('id')
            ->get()
            ->map(fn ($message) => ChatMessageResource::make($message))
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getFiles(Chat $chat): array
    {
        return ChatMessage::query()
            ->where('chat_id', $chat->getKey())
            ->whereHas('media', fn ($q) => $q->where('collection_name', 'files'))
            ->get()
            ->flatMap(fn ($message): MediaCollection => $message->getMedia('files'))
            ->map(fn ($media): array => new ChatFileResource($media)->toArray(request()))
            ->values()
            ->all();
    }
}
