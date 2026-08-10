<?php

declare(strict_types=1);

namespace Modules\Chat\Actions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Lorisleiva\Actions\Concerns\AsController;
use Modules\Chat\Enums\ChatStatusEnum;
use Modules\Chat\Models\Chat;

class SetBan
{
    use AsController;

    public function handle(Chat $chat, Request $request): JsonResponse
    {
        $user = $request->user();

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
