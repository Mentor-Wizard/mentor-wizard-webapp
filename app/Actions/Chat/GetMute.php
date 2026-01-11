<?php

declare(strict_types=1);

namespace App\Actions\Chat;

use Illuminate\Http\JsonResponse;
use Lorisleiva\Actions\Concerns\AsController;

class GetMute
{
    use AsController;

    public function handle(): JsonResponse
    {
        $user = auth()->user();

        return response()->json([
            'mute' => $user->profile->mute,
        ]);
    }
}
