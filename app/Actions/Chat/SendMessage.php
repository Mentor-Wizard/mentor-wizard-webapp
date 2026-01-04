<?php

declare(strict_types=1);

namespace App\Actions\Chat;

use App\Events\Chats\ChatMessageEvent;
use App\Http\Requests\Chat\ChatMessageRequest;
use App\Http\Resources\ChatMessageResource;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Lorisleiva\Actions\Concerns\AsController;

class SendMessage
{
    use AsController;

    public function handle(User $receiver, ChatMessageRequest $request): JsonResponse
    {
        $user = auth()->user();
        $data = $request->validated();
        $message = ChatMessage::query()->create([
            'sender_id'   => $user->id,
            'receiver_id' => $receiver->id,
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
        event(new ChatMessageEvent($message));

        return response()->json([
            'message' => ChatMessageResource::make($message),
        ]);
    }
}
