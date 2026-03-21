<?php

declare(strict_types=1);

namespace App\Actions\Chat;

use App\Enums\ChatStatusEnum;
use App\Http\Requests\Chat\ChatMessageRequest;
use App\Models\Chat;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Http\JsonResponse;
use Lorisleiva\Actions\Concerns\AsController;
use Throwable;

class CreateChat
{
    use AsController;

    /**
     * @param  User  $user  - companion
     *
     * @throws Throwable
     */
    public function handle(User $user, ChatMessageRequest $request): JsonResponse
    {
        /** @var User $owner */
        $owner = auth()->user();
        /**
         * @var (Chat&object{
         *     pivot: Pivot&object{
         *         status: string,
         *         is_muted: bool
         *     }
         * })|null $companionChat
         */
        $companionChat = $owner->chats()
            ->whereHas('users', function ($q) use ($user): void {
                $q->where('users.id', $user->getKey());
            })
            ->first();
        if ($companionChat) {
            throw_if($companionChat->pivot->status === ChatStatusEnum::BANNED->value, AuthorizationException::class);

            return SendMessage::run($companionChat, $request);
        }

        $chat = Chat::query()->create([
            'name' => $user->profile->name,
        ]);

        $chat->users()->attach($user->getKey(), ['status' => ChatStatusEnum::ACTIVE->value]);
        $chat->users()->attach($owner->getKey(), ['status' => ChatStatusEnum::ACTIVE->value]);

        return SendMessage::run($chat, $request);
    }
}
