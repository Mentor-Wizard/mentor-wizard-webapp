<?php

declare(strict_types=1);

namespace App\Actions\Chat;

use App\Models\Chat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Lorisleiva\Actions\Concerns\AsController;

class SetMute
{
    use AsController;

    public function handle(Chat $chat, Request $request): JsonResponse
    {
        $user = $request->user();
        $isMuted = $request->boolean('isMuted');
        $user->chats()->updateExistingPivot(
            $chat->id,
            [
                'is_muted' => $isMuted,
            ]
        );

        return response()->json([
            'isMuted' => $isMuted,
        ]);
    }
}
