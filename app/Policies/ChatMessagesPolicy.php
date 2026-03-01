<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ChatMessage;
use App\Models\User;

class ChatMessagesPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ChatMessage $chatMessage): bool
    {
        if ($chatMessage->user_id === $user->getKey()) {
            return true;
        }

        $companion = $chatMessage->chat->companion($user);

        return $companion->id === $chatMessage->user_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ChatMessage $chatMessage): bool
    {
        return $chatMessage->user_id === $user->getKey();
    }
}
