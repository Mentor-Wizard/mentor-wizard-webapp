<?php

declare(strict_types=1);

namespace App\Broadcasting;

use App\Models\User;

class ChatChannel
{
    public function join(User $user, $id): bool
    {
        return $user->id === (int) $id;
    }
}
