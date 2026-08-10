<?php

declare(strict_types=1);

namespace Modules\Chat\Actions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Lorisleiva\Actions\Concerns\AsController;
use Modules\Chat\Models\Chat;

class SetMute
{
    use AsController;

    public function handle(Chat $chat, Request $request): JsonResponse
    {
        $user = $request->user();
        $isMuted = $request->boolean('isMuted');
        $user->chats()->updateExistingPivot(
            $chat->getKey(),
            [
                'is_muted' => $isMuted,
            ]
        );

        return response()->json([
            'isMuted' => $isMuted,
        ]);
    }
}
