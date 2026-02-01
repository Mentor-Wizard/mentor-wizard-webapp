<?php

declare(strict_types=1);

namespace App\Actions\Chat;

use App\Enums\ChatStatusEnum;
use App\Models\Chat;
use Illuminate\Http\JsonResponse;
use Lorisleiva\Actions\Concerns\AsController;

class SetArchive
{
    use AsController;

    public function handle(Chat $chat): JsonResponse
    {
        $user = auth()->user();
        $user->chats()->updateExistingPivot(
            $chat->id,
            [
                'status' => ChatStatusEnum::ARCHIVED->value,
            ]
        );

        return response()->json([
            'success' => true,
        ]);
    }
}
