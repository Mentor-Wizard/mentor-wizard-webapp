<?php

declare(strict_types=1);

namespace Modules\Chat\Actions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Lorisleiva\Actions\Concerns\AsController;
use Modules\Chat\Events\UnreadMessagesEvent;
use Modules\Chat\Http\Resources\ChatFileResource;
use Modules\Chat\Http\Resources\ChatMessageResource;
use Modules\Chat\Models\Chat;
use Modules\Chat\Models\ChatMessage;
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
