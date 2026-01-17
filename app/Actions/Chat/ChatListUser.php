<?php

declare(strict_types=1);

namespace App\Actions\Chat;

use App\Enums\ChatStatusEnum;
use App\Enums\RoleEnum;
use App\Enums\TagEnum;
use App\Models\Chat;
use App\Models\ChatMessage;
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
        $chats = $user->chats()
            ->with(
                [
                    'companionChat.owner.profile',
                    'companionChat.owner.mentorProfile',
                ])
            ->whereIn('status', [ChatStatusEnum::ACTIVE, ChatStatusEnum::BANNED])
            ->get();

        $listUsers = [];
        foreach ($chats as $chat) {
            $companion = $chat->companionChat->owner;
            $lastMessage = $this->getLastMessage($chat);
            $listUsers[] = [
                'id'            => $chat->id,
                'owner_id'      => $chat->owner_id,
                'name'          => $companion->profile->name.' '.$companion->profile->last_name,
                'avatar'        => $companion->profile->avatar,
                'slug'          => $companion->hasRole(RoleEnum::MENTOR->value) ? $companion->slug : null,
                'message'       => $lastMessage?->message,
                'online'        => false,
                'mute'          => $chat->mute,
                'ban'           => $chat->status === ChatStatusEnum::BANNED,
                'canSend'       => $chat->companionChat->status === ChatStatusEnum::ACTIVE,
                'is_read'       => $lastMessage?->is_read,
                'created_at'    => $lastMessage?->created_at?->format('d.m.Y'),
                'tags'          => $companion->mentorProfile?->mentorTags()->where('mentor_tags.type', TagEnum::STACK)->pluck('tag')->toArray(),
                'last'          => $lastMessage?->created_at ? $this->getLastDateInfo($lastMessage->created_at) : null,
            ];
        }

        return response()->json([
            'users' => $listUsers,
        ]);
    }

    private function getLastMessage(Chat $chat)
    {
        return ChatMessage::query()->whereHas('chat', function ($q) use ($chat): void {
            $q->whereIn('chat_id', [$chat->id, $chat->companion_chat_id]);
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
