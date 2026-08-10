<?php

declare(strict_types=1);

namespace Modules\Chat\Policies;

use App\Models\User;
use Modules\Chat\Models\Chat;

class ChatPolicy
{
    public function view(User $user, Chat $chat): bool
    {
        return $chat->users()->where('users.id', $user->getKey())->exists();
    }

    public function update(User $user, Chat $chat): bool
    {
        return $chat->users()->where('users.id', $user->getKey())->exists();
    }
}
