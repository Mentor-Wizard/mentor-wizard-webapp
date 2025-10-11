<?php

declare(strict_types=1);

namespace App\Actions\Pages\Chat;

use Inertia\Inertia;
use Inertia\Response;
use Lorisleiva\Actions\Concerns\AsController;

class GetChatPage
{
    use AsController;

    public function handle(): Response
    {
        $user = auth()->user();

        return Inertia::render('Chat/ChatPage', [
            'user' => $user,
        ]);
    }
}
