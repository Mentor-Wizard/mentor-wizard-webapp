<?php

declare(strict_types=1);

namespace Modules\Chat\Events;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UnreadMessagesEvent implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public User $user, public int $notificationsCount) {}

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

    /**
     * Pins the broadcast event name to the legacy `App\Events\Chats\UnreadMessagesEvent`
     * FQCN. See {@see ChatMessageEvent::broadcastAs()} for the full
     * rationale (laravel-echo's default `'App.Events'` namespace prefix reconstructs the
     * full legacy FQCN from `resources/js/UseCases/useCaseAlert.js`'s
     * `'Chats\\UnreadMessagesEvent'` listener string).
     */
    public function broadcastAs(): string
    {
        return 'App\Events\Chats\UnreadMessagesEvent';
    }
}
