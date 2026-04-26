<?php

declare(strict_types=1);

namespace App\Events\Chats;

use App\Http\Resources\ChatMessageResource;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatMessageEvent implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public User $user, public ChatMessage $message, public bool $isMuted) {}

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $message = new ChatMessageResource($this->message)->resolve();

        // When broadcasting to the recipient, the sender is always 'other'
        $message['sender'] = 'other';

        return [
            'message' => $message,
            'isMuted' => $this->isMuted,
        ];
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('Chat.'.$this->user->getKey()),
        ];
    }
}
