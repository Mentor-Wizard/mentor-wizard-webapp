<?php

declare(strict_types=1);

namespace App\Actions\Notifications;

use App\Http\Resources\NotificationResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Lorisleiva\Actions\Concerns\AsController;

class ListNotifications
{
    use AsController;

    public function handle(Request $request): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();

        $notifications = $user->notifications()
            ->latest()
            ->paginate(User::NOTIFICATIONS_PER_PAGE);

        return NotificationResource::collection($notifications);
    }
}
