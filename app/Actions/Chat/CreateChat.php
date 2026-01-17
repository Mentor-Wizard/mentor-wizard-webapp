<?php

declare(strict_types=1);

namespace App\Actions\Chat;

use App\Enums\ChatStatusEnum;
use App\Http\Requests\Chat\ChatMessageRequest;
use App\Models\Chat;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Lorisleiva\Actions\Concerns\AsController;
use Throwable;

class CreateChat
{
    use AsController;

    /**
     * @throws Throwable
     */
    public function handle(User $user, ChatMessageRequest $request): JsonResponse
    {
        $owner = auth()->user();
        $chat = Chat::query()->where('owner_id', $owner->id)
            ->whereHas('companionChat', function ($q) use ($user): void {
                $q->where('owner_id', $user->id);
            })
            ->first();
        if ($chat) {
            throw_if($chat->companionChat->status === ChatStatusEnum::BANNED, AuthorizationException::class);

            return SendMessage::run($chat, $request);
        }

        $chat = Chat::query()->create([
            'owner_id' => $owner->id,
            'status'   => ChatStatusEnum::ACTIVE,
        ]);
        $chatCompanion = Chat::query()->create([
            'owner_id' => $user->id,
            'status'   => ChatStatusEnum::ACTIVE,
        ]);
        $chat->companion_chat_id = $chatCompanion->id;
        $chat->save();

        $chatCompanion->companion_chat_id = $chat->id;
        $chatCompanion->save();

        return SendMessage::run($chat, $request);
    }
}
