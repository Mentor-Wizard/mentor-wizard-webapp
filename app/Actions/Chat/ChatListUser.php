<?php

declare(strict_types=1);

namespace App\Actions\Chat;

use App\Enums\ChatStatusEnum;
use App\Enums\RoleEnum;
use App\Enums\TagEnum;
use App\Events\Chats\UnreadMessagesEvent;
use App\Models\Chat;
use App\Models\ChatMessage;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Date;
use Lorisleiva\Actions\Concerns\AsController;
use Stevebauman\Purify\Facades\Purify;

class ChatListUser
{
    use AsController;

    public function handle(): JsonResponse
    {
        $user = auth()->user();
        $chats = $user->chats()
            ->wherePivotIn('status', [ChatStatusEnum::ACTIVE, ChatStatusEnum::BANNED])
            ->with([
                'users',
                'users.profile',
                'users.mentorProfile',
                'users.mentorProfile.mentorTags',
                'messages' => static function ($query): void {
                    $query->latest('id')->limit(1);
                },
            ])
            ->get();

        $listUsers = [];
        /** @var Chat $chat */
        /**
         * @var Chat&object{pivot: Pivot&object{status: string, is_muted: bool}} $chat
         */
        $userId = $user->getKey();
        foreach ($chats as $chat) {
            $companion = $chat->users
                ->where('id', '!=', $userId)
                ->first();

            /** @var ?ChatMessage $lastMessage */
            $lastMessage = $chat->messages->first();
            $companionChatPivot = $companion?->pivot;

            $tags = null;
            if ($companion?->mentorProfile) {
                $tags = $companion->mentorProfile->mentorTags
                    ->where('type', TagEnum::STACK)
                    ->pluck('tag')
                    ->toArray();
                if ($tags === []) {
                    $tags = null;
                }
            }

            $listUsers[] = [
                'id'           => $companion?->getKey(),
                'chatId'       => $chat->getKey(),
                'name'         => $companion?->profile ? $companion->profile->name.' '.$companion->profile->last_name : null,
                'avatar'       => $companion?->profile->avatar,
                'slug'         => $companion?->hasRole(RoleEnum::MENTOR->value) ? $companion->slug : null,
                'message'      => Purify::clean($lastMessage?->message ?? ''),
                'online'       => false,
                'isMuted'      => $chat->pivot->is_muted,
                'ban'          => $chat->pivot->status === ChatStatusEnum::BANNED->value,
                'canSend'      => $companionChatPivot?->status === ChatStatusEnum::ACTIVE->value,
                'isRead'       => $lastMessage?->is_read,
                'createdAt'    => $lastMessage?->created_at->format('d.m.Y'),
                'tags'         => $tags,
                'last'         => $lastMessage instanceof ChatMessage ? $this->getLastDateInfo($lastMessage->created_at) : null,
            ];
        }

        event(new UnreadMessagesEvent($user, UnreadMessages::run($user)));

        return response()->json([
            'users' => $listUsers,
        ]);
    }

    private function getLastDateInfo(DateTimeInterface $createdAt): string
    {
        $carbonDate = Date::instance($createdAt);

        return $carbonDate->diffForHumans();
    }
}
