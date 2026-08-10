<?php

declare(strict_types=1);

namespace Modules\Chat\Broadcasting;

use App\Models\User;

class ChatChannel
{
    public function join(User $user, int|string $id): bool
    {
        return $user->getKey() === (int) $id;
    }
}
