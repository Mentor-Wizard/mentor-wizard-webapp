<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Modules\Chat\Events\UnreadMessagesEvent;

describe('UnreadMessagesEvent', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
    });

    it('pins the broadcast event name to the legacy FQCN regardless of the class namespace', function (): void {
        $user = User::factory()->create();

        $event = new UnreadMessagesEvent($user, 3);

        expect($event->broadcastAs())->toBe('App\Events\Chats\UnreadMessagesEvent');
    });

    it('broadcasts on the private Chat.{id} channel', function (): void {
        $user = User::factory()->create();

        $event = new UnreadMessagesEvent($user, 0);

        expect($event->broadcastOn()[0]->name)->toBe('private-Chat.'.$user->getKey());
    });
});
