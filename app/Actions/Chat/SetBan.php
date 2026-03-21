<?php

declare(strict_types=1);

namespace App\Actions\Chat;

use App\Enums\ChatStatusEnum;
use App\Models\Chat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Lorisleiva\Actions\Concerns\AsController;

class SetBan
{
    use AsController;

    public function handle(Chat $chat, Request $request): JsonResponse
    {
        $user = auth()->user();

        $status = $request->boolean('ban')
            ? ChatStatusEnum::BANNED->value
            : ChatStatusEnum::ACTIVE->value;

        $user->chats()->updateExistingPivot(
            $chat->getKey(),
            [
                'status' => $status,
            ]
        );

        return response()->json([
            'ban' => $status === ChatStatusEnum::BANNED->value,
        ]);
    }
}
