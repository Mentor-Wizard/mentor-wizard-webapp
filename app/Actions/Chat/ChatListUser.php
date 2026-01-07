<?php

declare(strict_types=1);

namespace App\Actions\Chat;

use App\Enums\TagEnum;
use App\Models\ChatMessage;
use App\Models\User;
use DateTimeInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Date;
use Lorisleiva\Actions\Concerns\AsController;

class ChatListUser
{
    use AsController;

    public function handle(): JsonResponse
    {
        $user = auth()->user();
        $companions = $this->getCompanions($user);
        $listUsers = [];
        foreach ($companions as $companion) {
            $lastMessage = $this->getLastMessage($user, $companion);
            $userCompanion = $lastMessage->sender_id === $user->id ? $lastMessage->userReceiver : $lastMessage->userSender;
            $listUsers[] = [
                'id'         => $userCompanion->id,
                'name'       => $userCompanion->profile->name.' '.$userCompanion->profile->last_name,
                'avatar'     => $userCompanion->profile->avatar,
                'message'    => $lastMessage->message,
                'online'     => false,
                'is_read'    => $lastMessage->is_read,
                'created_at' => $lastMessage->created_at->format('d.m.Y'),
                'tags'       => $userCompanion->mentorProfile?->mentorTags()->where('mentor_tags.type', TagEnum::STACK)->pluck('tag')->toArray(),
                'last'       => $this->getLastDateInfo($lastMessage->created_at),
            ];
        }

        return response()->json([
            'users' => $listUsers,
        ]);
    }

    private function getCompanions(User $user): array
    {
        // We get everyone to whom the user sent messages
        $senders = ChatMessage::query()->where('sender_id', $user->id)
            ->distinct()
            ->pluck('receiver_id');

        // We get everyone who sent messages to the user
        $receivers = ChatMessage::query()->where('receiver_id', $user->id)
            ->distinct()
            ->pluck('sender_id');

        return $senders->merge($receivers)->unique()->values()->toArray();
    }

    private function getLastMessage(User $user, int $otherUserId)
    {
        return ChatMessage::query()
            ->with(['userSender', 'userReceiver'])
            ->where(function ($query) use ($user, $otherUserId): void {
                $query->where(function ($q) use ($user, $otherUserId): void {
                    $q->where('sender_id', $user->id)
                        ->where('receiver_id', $otherUserId);
                })->orWhere(function ($q) use ($user, $otherUserId): void {
                    $q->where('sender_id', $otherUserId)
                        ->where('receiver_id', $user->id);
                });
            })
            ->latest('id')
            ->first();
    }

    private function getLastDateInfo(DateTimeInterface $created_at): string
    {
        $carbonDate = Date::instance($created_at);

        return $carbonDate->diffForHumans();
    }
}
