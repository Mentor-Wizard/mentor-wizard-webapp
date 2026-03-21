<?php

declare(strict_types=1);

namespace App\Actions\Chat;

use App\Events\Chats\UnreadMessagesEvent;
use App\Models\Chat;
use App\Models\ChatMessage;
use App\Repositories\Chat\ChatMessageRepository;
use Illuminate\Http\JsonResponse;
use Lorisleiva\Actions\Concerns\AsController;

class ChatMessages
{
    use AsController;

    public function __construct(private readonly ChatMessageRepository $repository) {}

    public function handle(Chat $chat): JsonResponse
    {
        $user = auth()->user();
        $this->setReadMessages($chat);
        event(new UnreadMessagesEvent($user, UnreadMessages::run($user)));

        return response()->json([
            'messages' => $this->repository->getMessages($chat),
            'files'    => $this->repository->getFiles($chat),
        ]);
    }

    private function setReadMessages(Chat $chat): void
    {
        ChatMessage::query()
            ->where('chat_id', $chat->getKey())
            ->update(['is_read' => true]);
    }
}
