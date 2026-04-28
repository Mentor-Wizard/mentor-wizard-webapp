<?php

declare(strict_types=1);

namespace App\Actions\Notifications;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Date;
use Lorisleiva\Actions\Concerns\AsController;

class MarkAllNotificationsAsRead
{
    use AsController;

    public function handle(Request $request): Response
    {
        $request->user()?->unreadNotifications()->update(['read_at' => Date::now()]);

        return response()->noContent();
    }
}
