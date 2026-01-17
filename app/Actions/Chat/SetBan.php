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
        $chat->status = ChatStatusEnum::ACTIVE;
        if ((bool) $request->input('ban', 0)) {
            $chat->status = ChatStatusEnum::BANNED;
        }

        $chat->save();

        return response()->json([
            'ban' => $chat->status === ChatStatusEnum::BANNED,
        ]);
    }
}
