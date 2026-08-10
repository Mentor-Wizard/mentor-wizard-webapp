<?php

declare(strict_types=1);

namespace Modules\Chat\Actions;

use Illuminate\Http\JsonResponse;
use Lorisleiva\Actions\Concerns\AsController;
use Modules\Chat\Events\UnreadMessagesEvent;
use Modules\Chat\Http\Resources\ChatMessageResource;
use Modules\Chat\Models\ChatMessage;
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
