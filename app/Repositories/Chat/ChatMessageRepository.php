<?php

declare(strict_types=1);

namespace App\Repositories\Chat;

use App\Http\Resources\ChatFileResource;
use App\Http\Resources\ChatMessageResource;
use App\Models\ChatMessage;
use App\Models\User;
use Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection;

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

    public function getFiles(User $user, User $receiver): array
    {
        return ChatMessage::query()
            ->where(function ($query) use ($user, $receiver): void {
                $query->where(function ($q) use ($user, $receiver): void {
                    $q->where('sender_id', $user->id)
                        ->where('receiver_id', $receiver->id);
                })->orWhere(function ($q) use ($user, $receiver): void {
                    $q->where('sender_id', $receiver->id)
                        ->where('receiver_id', $user->id);
                });
            })
            ->whereHas('media', fn ($q) => $q->where('collection_name', 'files'))
            ->get()
            ->flatMap(fn ($message): MediaCollection => $message->getMedia('files'))
            ->map(fn ($media): array => new ChatFileResource($media)->toArray(request()))
            ->values()
            ->all();
    }
}
