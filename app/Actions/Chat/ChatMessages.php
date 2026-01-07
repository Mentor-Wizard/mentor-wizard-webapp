<?php

declare(strict_types=1);

namespace App\Actions\Chat;

use App\Models\ChatMessage;
use App\Models\User;
use App\Repositories\Chat\ChatMessageRepository;
use Illuminate\Http\JsonResponse;
use Lorisleiva\Actions\Concerns\AsController;

class ChatMessages
{
    use AsController;

    public function __construct(private readonly ChatMessageRepository $repository) {}

    public function handle(User $receiver): JsonResponse
    {
        $user = auth()->user();
        $this->setReadMessages($user, $receiver);

        return response()->json([
            'messages' => $this->repository->getMessages($user, $receiver),
            'files'    => $this->repository->getFiles($user, $receiver),
        ]);
    }

    private function setReadMessages(User $user, User $receiver): void
    {
        ChatMessage::query()
            ->where('sender_id', $receiver->id)
            ->where('receiver_id', $user->id)
            ->update(['is_read' => true]);
    }
}
