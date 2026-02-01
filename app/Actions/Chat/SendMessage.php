<?php

declare(strict_types=1);

namespace App\Actions\Chat;

use App\Enums\ChatStatusEnum;
use App\Events\Chats\ChatMessageEvent;
use App\Events\Chats\UnreadMessagesEvent;
use App\Http\Requests\Chat\ChatMessageRequest;
use App\Http\Resources\ChatMessageResource;
use App\Models\Chat;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Lorisleiva\Actions\Concerns\AsAction;
use Lorisleiva\Actions\Concerns\AsController;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;
use Throwable;

class SendMessage
{
    use AsAction;
    use AsController;

    /**
     * @throws Throwable
     * @throws FileDoesNotExist
     * @throws FileIsTooBig
     */
    public function handle(Chat $chat, ChatMessageRequest $request): JsonResponse
    {
        $user = auth()->user();
        $companion = $chat->companion($user);
        $companionChat = $companion->chats()
            ->wherePivot('chat_id', $chat->id)
            ->wherePivot('user_id', $companion->id)
            ->first();
        throw_if($companionChat->pivot->status !== ChatStatusEnum::ACTIVE->value, AuthorizationException::class);

        $data = $request->validated();
        $message = ChatMessage::query()->create([
            'chat_id'     => $chat->id,
            'user_id'     => $user->id,
            'message'     => $data['message'],
            'is_read'     => false,
        ]);
        if (isset($data['files'])) {
            foreach ($data['files'] as $file) {
                if ($file instanceof UploadedFile) {
                    $message->addMedia($file)->toMediaCollection('files');
                }
            }
        }

        $message->refresh();
        /** @var User $companion */
        $companion = $chat->companion($user);
        event(new ChatMessageEvent($companion, $message, $companionChat->pivot->is_muted));
        event(new UnreadMessagesEvent($companion, UnreadMessages::run($companion)));

        return response()->json([
            'message' => ChatMessageResource::make($message),
        ]);
    }
}
