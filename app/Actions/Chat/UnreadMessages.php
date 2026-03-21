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
            ->whereHas('chat.users', function ($query) use ($user): void {
                $query->where('users.id', $user->getKey());
            })
            ->where('is_read', false)
            ->where('user_id', '<>', $user->getKey())
            ->count();
    }
}
