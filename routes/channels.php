<?php

declare(strict_types=1);

use App\Broadcasting\ChatChannel;
use App\Broadcasting\OnlineUsersChannel;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', fn ($user, $id): bool => (int) $user->id === (int) $id);
Broadcast::channel('presence-online-users', OnlineUsersChannel::class);
Broadcast::channel('Chat.{id}', ChatChannel::class);
