<?php

declare(strict_types=1);

use App\Events\Chats\ChatMessageEvent;
use App\Models\Chat;
use App\Models\ChatMessage;
use App\Models\User;
use Database\Seeders\RoleSeeder;

describe('ChatMessageEvent', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
    });

    it('formats broadcast data correctly and forces sender to other', function (): void {
        $sender = User::factory()->create();
        $sender->profile()->create([
            'name' => 'Sender Name',
        ]);

        $recipient = User::factory()->create();
        $recipient->profile()->create([
            'name' => 'Recipient Name',
        ]);

        $chat = Chat::factory()->create();

        $message = ChatMessage::factory()->create([
            'chat_id' => $chat->id,
            'user_id' => $sender->id,
            'message' => 'Test broadcast message',
            'is_read' => false,
        ]);

        $isMuted = true;
        $event = new ChatMessageEvent($recipient, $message, $isMuted);

        $payload = $event->broadcastWith();

        expect($payload)->toHaveKeys(['message', 'isMuted'])
            ->and($payload['isMuted'])->toBeTrue()
            ->and($payload['message']['id'])->toBe($message->id)
            ->and($payload['message']['user_id'])->toBe($sender->id)
            ->and($payload['message']['message'])->toBe('Test broadcast message')
            ->and($payload['message']['sender'])->toBe('other'); // Crucial assertion
    });

    it('cleans XSS in the broadcast payload', function (): void {
        $sender = User::factory()->create();
        $sender->profile()->create(['name' => 'Sender']);

        $recipient = User::factory()->create();
        $recipient->profile()->create(['name' => 'Recipient']);

        $chat = Chat::factory()->create();

        $message = ChatMessage::factory()->create([
            'chat_id' => $chat->id,
            'user_id' => $sender->id,
            'message' => '<script>alert("XSS")</script>Safe Text',
            'is_read' => false,
        ]);

        $event = new ChatMessageEvent($recipient, $message, false);

        $payload = $event->broadcastWith();

        expect($payload['message']['message'])->not->toContain('<script>')
            ->and($payload['message']['message'])->toContain('Safe Text');
    });
});
