<?php

declare(strict_types=1);

namespace Modules\Chat\Actions;

use App\Enums\RoleEnum;
use App\Enums\TagEnum;
use App\Models\User;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Lorisleiva\Actions\Concerns\AsController;
use Modules\Chat\Enums\ChatStatusEnum;
use Modules\Chat\Events\UnreadMessagesEvent;
use Modules\Chat\Models\Chat;
use Modules\Chat\Models\ChatMessage;
use Stevebauman\Purify\Facades\Purify;

class ChatListUser
{
    use AsController;

    public function handle(Request $request): JsonResponse
    {
        $user = $request->user();
        $userId = $user->getKey();

        $chats = $this->getChatsWithRelations($user);

        $listUsers = [];
        /** @var Chat $chat */
        foreach ($chats as $chat) {
            $listUsers[] = $this->buildChatUserItem($chat, $userId);
        }

        event(new UnreadMessagesEvent($user, UnreadMessages::run($user)));

        return response()->json([
            'users' => $listUsers,
        ]);
    }

    /**
     * @return Collection<int, Chat>
     */
    private function getChatsWithRelations(User $user)
    {
        return $user->chats()
            ->wherePivotIn('status', [ChatStatusEnum::ACTIVE, ChatStatusEnum::BANNED])
            ->with([
                'users.profile',
                'users.mentorProfile.mentorTags',
                'messages' => static function ($query): void {
                    $query->latest('id')->limit(1);
                },
            ])
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    private function buildChatUserItem(Chat $chat, int $userId): array
    {
        $companion = $chat->users
            ->where('id', '!=', $userId)
            ->first();

        /** @var ?ChatMessage $lastMessage */
        $lastMessage = $chat->messages->first();
        /** @var Pivot|null $companionChatPivot */
        $companionChatPivot = $companion?->pivot;

        $tags = $this->getCompanionTags($companion);

        $chatUser = $chat->users->where('id', $userId)->first();

        return [
            'id'           => $companion?->getKey(),
            'chatId'       => $chat->getKey(),
            'name'         => $companion?->profile ? $companion->profile->name.' '.$companion->profile->last_name : null,
            'avatar'       => $companion?->profile?->avatar,
            'slug'         => $companion?->hasRole(RoleEnum::MENTOR->value) ? $companion->slug : null,
            'message'      => $lastMessage ? Purify::clean($lastMessage->message) : '',
            'online'       => false,
            'isMuted'      => $this->getPivotValue($chatUser?->pivot, 'is_muted'),
            'ban'          => $this->getPivotValue($chatUser?->pivot, 'status') === ChatStatusEnum::BANNED->value,
            'canSend'      => $this->getPivotValue($companionChatPivot, 'status') === ChatStatusEnum::ACTIVE->value,
            'isRead'       => $lastMessage?->is_read,
            'createdAt'    => $lastMessage?->created_at->format('d.m.Y'),
            'tags'         => $tags,
            'last'         => $lastMessage instanceof ChatMessage ? $this->getLastDateInfo($lastMessage->created_at) : null,
        ];
    }

    /**
     * @return mixed
     */
    private function getPivotValue(?Pivot $pivot, string $key)
    {
        if (! $pivot instanceof Pivot) {
            return null;
        }

        return $pivot->{$key};
    }

    /**
     * @return array<string>|null
     */
    private function getCompanionTags(?User $companion): ?array
    {
        if (! $companion?->mentorProfile) {
            return null;
        }

        $tags = $companion->mentorProfile->mentorTags
            ->where('type', TagEnum::STACK)
            ->pluck('tag')
            ->toArray();

        return $tags === [] ? null : $tags;
    }

    private function getLastDateInfo(DateTimeInterface $createdAt): string
    {
        $carbonDate = Date::instance($createdAt);

        return $carbonDate->diffForHumans();
    }
}
