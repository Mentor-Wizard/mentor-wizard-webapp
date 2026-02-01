<?php

declare(strict_types=1);

namespace App\Actions\Chat;

use App\Models\Chat;
use Illuminate\Http\JsonResponse;
use Lorisleiva\Actions\Concerns\AsController;

class GetMute
{
    use AsController;

    public function handle(Chat $chat): JsonResponse
    {
        return response()->json([
            'isMuted' => $chat->is_muted,
        ]);
    }
}
