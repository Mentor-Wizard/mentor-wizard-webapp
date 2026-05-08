<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Chat;
use App\Models\User;

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
