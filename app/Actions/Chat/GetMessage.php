<?php

declare(strict_types=1);

namespace App\Actions\Chat;

use App\Http\Resources\ChatMessageResource;
use App\Models\ChatMessage;
use Illuminate\Auth\Access\AuthorizationException;
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
        $user = auth()->user();
        throw_if($message->receiver_id !== $user->id, AuthorizationException::class);
        $message->is_read = true;
        $message->save();

        return response()->json([
            'message' => ChatMessageResource::make($message),
        ]);
    }
}
