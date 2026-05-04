<?php

declare(strict_types=1);

namespace App\Actions\Chat;

use App\Events\Chats\UnreadMessagesEvent;
use App\Http\Resources\ChatMessageResource;
use App\Models\ChatMessage;
use Illuminate\Http\JsonResponse;
use Lorisleiva\Actions\Concerns\AsController;
use Throwable;

class GetMessage
{
    use AsController;

    /**
     * @throws Throwable
     */
    public function handle(ChatMessage $message): JsonResponse
    {
        $message->is_read = true;
        $message->save();

        $companion = $message->chat->companion($message->user);
        event(new UnreadMessagesEvent($companion, UnreadMessages::run($message->user)));

        return response()->json([
            'message' => ChatMessageResource::make($message),
        ]);
    }
}
