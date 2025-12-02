<?php

declare(strict_types=1);

namespace App\Actions\Chat;

use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Http\JsonResponse;
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
            // $userCompanion = $lastMessage->sender_id === $user->id ? $lastMessage->receiver_id : $lastMessage->sender_id;
            $listUsers[] = [
                'id'          => $user->id,
                'id2'         => $companion,
                'id3'         => $lastMessage->id,
                /*'name'       => $userCompanion->profile->name.' '.$userCompanion->profile->last_name,
                'avatar'     => $userCompanion->profile->avatar,
                'message'    => $lastMessage->message,
                'online'     => false,
                'created_at' => $lastMessage->created_at,*/
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
        $query = ChatMessage::query()
            ->with(['userSender', 'userReceiver'])
            ->where(function ($query) use ($user, $otherUserId): void {
                $query->where([
                    ['sender_id', $user->id],
                    ['receiver_id', $otherUserId],
                ])->orWhere([
                    ['sender_id', $otherUserId],
                    ['receiver_id', $user->id],
                ]);
            })
            ->latest('id');
        $bindings = $query->getBindings();
        $sql = $query->toSql();

        $query->toSql();
        $res = ChatMessage::query()
            ->with(['userSender', 'userReceiver'])
            ->where(function ($query) use ($user, $otherUserId): void {
                $query->where([
                    ['sender_id', $user->id],
                    ['receiver_id', $otherUserId],
                ])->orWhere([
                    ['sender_id', $otherUserId],
                    ['receiver_id', $user->id],
                ]);
            })
            ->latest('id')
            ->first();
        dd($sql, $bindings, $res->id);

    }
}
