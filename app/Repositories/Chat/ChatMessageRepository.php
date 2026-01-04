<?php

declare(strict_types=1);

namespace App\Repositories\Chat;

use App\Http\Resources\ChatMessageResource;
use App\Models\ChatMessage;
use App\Models\User;

class ChatMessageRepository
{
    public function getMessages(User $user, User $receiver): array
    {
        return ChatMessage::query()
            ->with(['userSender.profile', 'userReceiver.profile'])
            ->where(function ($query) use ($user, $receiver): void {
                $query->where(function ($q) use ($user, $receiver): void {
                    $q->where('sender_id', $user->id)
                        ->where('receiver_id', $receiver->id);
                })->orWhere(function ($q) use ($user, $receiver): void {
                    $q->where('sender_id', $receiver->id)
                        ->where('receiver_id', $user->id);
                });
            })
            ->orderBy('id')
            ->get()
            ->map(fn ($message) => ChatMessageResource::make($message))
            ->all();
    }
}
