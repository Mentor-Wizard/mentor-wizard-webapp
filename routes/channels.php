<?php

declare(strict_types=1);

use App\Broadcasting\OnlineUsersChannel;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', fn ($user, $id): bool => (int) $user->id === (int) $id);
Broadcast::channel('presence-online-users', OnlineUsersChannel::class);

// 'Chat.{id}' is registered in Modules\Chat\Providers\ChatServiceProvider::boot() —
// see docs/plans/ddd-migration-laravel-modules for the DDD-migration Chat pilot.
