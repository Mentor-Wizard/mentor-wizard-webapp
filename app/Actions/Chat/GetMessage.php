<?php

declare(strict_types=1);

namespace App\Actions\Chat;

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

        return response()->json([
            'message' => ChatMessageResource::make($message),
        ]);
    }
}
