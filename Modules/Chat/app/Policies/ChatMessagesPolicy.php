<?php

declare(strict_types=1);

namespace Modules\Chat\Policies;

use App\Models\User;
use Modules\Chat\Models\Chat;
use Modules\Chat\Models\ChatMessage;

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
        /** @var Chat|null $chat */
        $chat = $chatMessage->chat;

        return $chat?->users()->where('users.id', $user->getKey())->exists() ?? false;
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
