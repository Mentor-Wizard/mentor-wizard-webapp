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
use Modules\Chat\Http\Resources\ChatMessageResource;
use Modules\Chat\Models\ChatMessage;

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

    /**
     * Pins the broadcast event name to the legacy `App\Events\Chats\ChatMessageEvent`
     * FQCN, independent of this class's current namespace.
     *
     * `resources/js/UseCases/useCaseAlert.js` listens for `'Chats\\ChatMessageEvent'`.
     * laravel-echo's `EventFormatter` prepends its default `namespace` option
     * (`'App.Events'`, see node_modules/laravel-echo/dist/echo.js) to any listener
     * string that doesn't start with `.`/`\`, then converts every `.` to `\`. So the
     * wire event name the frontend actually matches against is the full legacy FQCN
     * `App\Events\Chats\ChatMessageEvent` — not the short `Chats\ChatMessageEvent`
     * suffix. Moving this class into `Modules\Chat\Events` would otherwise silently
     * change Laravel's default broadcast name and break the chat bell/unread counter
     * with no error (frontend is explicitly out of scope for this migration).
     */
    public function broadcastAs(): string
    {
        return 'App\Events\Chats\ChatMessageEvent';
    }
}
