<?php

declare(strict_types=1);

namespace App\Broadcasting;

use App\Models\User;

class OnlineUsersChannel
{
    /**
     * Authenticate the user's access to the channel.
     *
     * @return array<string, mixed>
     */
    public function join(User $user): array|bool
    {
        return [
            'id' => $user->getKey(),
        ];
    }
}
