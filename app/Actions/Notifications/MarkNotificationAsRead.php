<?php

declare(strict_types=1);

namespace App\Actions\Notifications;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Date;
use Lorisleiva\Actions\Concerns\AsController;

class MarkNotificationAsRead
{
    use AsController;

    public function handle(Request $request, string $id): Response
    {
        $request->user()
            ?->notifications()
            ->where('id', $id)
            ->update(['read_at' => Date::now()]);

        return response()->noContent();
    }
}
