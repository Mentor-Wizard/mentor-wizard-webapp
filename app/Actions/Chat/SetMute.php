<?php

declare(strict_types=1);

namespace App\Actions\Chat;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Lorisleiva\Actions\Concerns\AsController;

class SetMute
{
    use AsController;

    public function handle(Request $request): JsonResponse
    {
        $user = auth()->user();
        $user->profile->mute = (bool) $request->input('mute', 0);
        $user->profile->save();

        return response()->json([
            'success' => true,
        ]);
    }
}
