<?php

declare(strict_types=1);

namespace App\Actions\Chat;

use App\Enums\ChatStatusEnum;
use App\Enums\RoleEnum;
use App\Enums\TagEnum;
use App\Events\Chats\UnreadMessagesEvent;
use App\Models\Chat;
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
        $chats = $user->chats()
            ->wherePivotIn('status', [ChatStatusEnum::ACTIVE, ChatStatusEnum::BANNED])
            ->get();

        $listUsers = [];
        /** @var Chat $chat */
        foreach ($chats as $chat) {
            /** @var User $companion */
            $companion = $chat->companion($user);
            $companionChat = $companion->chats()
                ->wherePivot('chat_id', $chat->id)
                ->wherePivot('user_id', $companion->id)
                ->first();
            $lastMessage = $this->getLastMessage($chat);
            $listUsers[] = [
                'id'           => $companion->id,
                'chatId'       => $chat->id,
                'name'         => $companion->profile->name.' '.$companion->profile->last_name,
                'avatar'       => $companion->profile->avatar,
                'slug'         => $companion->hasRole(RoleEnum::MENTOR->value) ? $companion->slug : null,
                'message'      => $lastMessage?->message,
                'online'       => false,
                'isMuted'      => $chat->pivot->is_muted,
                'ban'          => $chat->pivot->status === ChatStatusEnum::BANNED->value,
                'canSend'      => $companionChat->pivot->status === ChatStatusEnum::ACTIVE->value,
                'isRead'       => $lastMessage?->is_read,
                'createdAt'    => $lastMessage?->created_at->format('d.m.Y'),
                'tags'         => $companion->mentorProfile?->mentorTags()->where('mentor_tags.type', TagEnum::STACK)->pluck('tag')->toArray(),
                'last'         => $lastMessage ? $this->getLastDateInfo($lastMessage->created_at) : null,
            ];
        }

        event(new UnreadMessagesEvent($user, UnreadMessages::run($user)));

        return response()->json([
            'users' => $listUsers,
        ]);
    }

    private function getLastMessage(Chat $chat)
    {
        return ChatMessage::query()
            ->where('chat_id', $chat->id)
            ->latest('id')
            ->first();
    }

    private function getLastDateInfo(DateTimeInterface $createdAt): string
    {
        $carbonDate = Date::instance($createdAt);

        return $carbonDate->diffForHumans();
    }
}
