<?php

declare(strict_types=1);

namespace Modules\Chat\Actions;

use App\Models\User;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Chat\Models\ChatMessage;

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
