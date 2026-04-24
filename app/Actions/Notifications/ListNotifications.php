<?php

declare(strict_types=1);

namespace App\Actions\Notifications;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Lorisleiva\Actions\Concerns\AsController;

class ListNotifications
{
    use AsController;

    public function handle(Request $request): JsonResponse
    {
        $notifications = $request->user()
            ?->notifications()
            ->latest()
            ->limit(20)
            ->get() ?? collect();

        return response()->json($notifications);
    }
}
