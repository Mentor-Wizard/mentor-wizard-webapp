<?php

declare(strict_types=1);

namespace Modules\Chat\Actions\Pages;

use Inertia\Inertia;
use Inertia\Response;
use Lorisleiva\Actions\Concerns\AsController;

class GetChatPage
{
    use AsController;

    public function handle(): Response
    {
        return Inertia::render('Chat/ChatPage');
    }
}
