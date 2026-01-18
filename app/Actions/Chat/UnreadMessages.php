<?php

declare(strict_types=1);

namespace App\Actions\Chat;

use App\Models\ChatMessage;
use App\Models\User;
use Lorisleiva\Actions\Concerns\AsAction;

class UnreadMessages
{
    use AsAction;

    public function handle(User $user): int
    {
        return ChatMessage::query()
            ->where('is_read', false)
            ->whereIn('chat_id', function ($query) use ($user): void {
                $query->select('companion_chat_id')
                    ->from('chats')
                    ->where('owner_id', $user->id);
            })
            ->count();
    }
}
