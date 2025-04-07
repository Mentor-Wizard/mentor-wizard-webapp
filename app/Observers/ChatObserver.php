<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Chat;
use Illuminate\Support\Facades\Log;

class ChatObserver
{
    public function saved(Chat $chat): void
    {
        if (is_null($chat->mentor_id) && is_null($chat->menti_id) && is_null($chat->coach_id)) {

            Log::info('Deleting chat because all user IDs are NULL.', [
                'chat' => $chat->getKey(),
            ]);

            $chat->delete();
        }
    }
}
