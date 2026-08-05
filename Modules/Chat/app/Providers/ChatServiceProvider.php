<?php

declare(strict_types=1);

namespace Modules\Chat\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Modules\Chat\Broadcasting\ChatChannel;
use Modules\Chat\Models\Chat;
use Modules\Chat\Models\ChatMessage;
use Modules\Chat\Policies\ChatMessagesPolicy;
use Modules\Chat\Policies\ChatPolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;
use Override;

class ChatServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    #[Override]
    protected string $name = 'Chat';

    /**
     * The lowercase version of the module name.
     */
    #[Override]
    protected string $nameLower = 'chat';

    /**
     * Provider classes to register.
     *
     * @var string[]
     */
    #[Override]
    protected array $providers = [
        RouteServiceProvider::class,
    ];

    #[Override]
    public function boot(): void
    {
        parent::boot();

        Gate::policy(Chat::class, ChatPolicy::class);
        Gate::policy(ChatMessage::class, ChatMessagesPolicy::class);

        Broadcast::channel('Chat.{id}', ChatChannel::class);

        RateLimiter::for('chat-send', fn (Request $request): Limit => Limit::perMinute(60)->by($request->user()?->getKey() ?: $request->ip()));
        RateLimiter::for('chat-create', fn (Request $request): Limit => Limit::perMinute(10)->by($request->user()?->getKey() ?: $request->ip()));
    }
}
