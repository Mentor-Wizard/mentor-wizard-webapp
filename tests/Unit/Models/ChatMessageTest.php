<?php

declare(strict_types=1);

use App\Models\Chat;
use App\Models\ChatMessage;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\Eloquent\MassAssignmentException;

covers(ChatMessage::class);

describe('ChatMessage Model', function () {
    beforeEach(function () {
        $this->seed(RoleSeeder::class);
        $this->mentor = User::factory()->create();
        $this->menti = User::factory()->create();
        $this->coach = User::factory()->create();

        $this->chat = Chat::factory()->create([
            'mentor_id' => $this->mentor->getKey(),
            'menti_id' => $this->menti->getKey(),
            'coach_id' => $this->coach->getKey(),
        ]);
    });

    it('can create chat message with basic attributes with relations', function () {
        $chatMessage = ChatMessage::factory()->create([
            'chat_id' => $this->chat->getKey(),
            'user_id' => $this->menti->getKey(),
            'message' => 'Test message',
            'is_read' => false,
        ]);

        expect($chatMessage)->toBeInstanceOf(ChatMessage::class)
            ->and($chatMessage->chat_id)->toBe($this->chat->getKey())
            ->and($chatMessage->user_id)->toBe($this->menti->getKey())
            ->and($chatMessage->message)->toBe('Test message')
            ->and($chatMessage->is_read)->toBe(false)
            ->and($chatMessage->chat)->toBeInstanceOf(Chat::class)
            ->and($chatMessage->chat->getKey())->toBe($this->chat->getKey());
    });

    it('can create chat message with basic attributes and casts are correct', function () {
        $chatMessage = ChatMessage::factory()->create([
            'chat_id' => $this->chat->getKey(),
            'user_id' => $this->menti->getKey(),
            'message' => 'Test message',
            'is_read' => false,
        ]);

        expect($chatMessage)->toBeInstanceOf(ChatMessage::class)
            ->and($chatMessage->chat_id)->toBeInt()
            ->and($chatMessage->user_id)->toBeInt()
            ->and($chatMessage->message)->toBeString()
            ->and($chatMessage->is_read)->toBeBool();
    });

    it('cascades on chat deletion', function () {
        $chatMessage = ChatMessage::factory()->create([
            'chat_id' => $this->chat->getKey(),
            'user_id' => $this->menti->getKey(),
            'message' => 'Test message',
            'is_read' => false,
        ]);

        $this->chat->delete();

        $this->assertDatabaseMissing('chat_messages', ['id' => $chatMessage->getKey()]);
    });

    it('cascades on user deletion', function () {
        $chatMessage = ChatMessage::factory()->create([
            'chat_id' => $this->chat->getKey(),
            'user_id' => $this->menti->getKey(),
            'message' => 'Test message',
            'is_read' => false,
        ]);

        $this->menti->delete();

        $this->assertDatabaseHas('chat_messages', [
            'id' => $chatMessage->getKey(),
            'user_id' => null,
        ]);
    });

    it('has correctly defined fillable attributes', function () {
        $chatMessage = new ChatMessage;
        expect($chatMessage->getFillable())->toBe([
            'chat_id',
            'user_id',
            'message',
            'is_read',
        ]);

    });

    it('throws an exception when mass assigning unauthorized attributes', function () {
        $chatMessage = new ChatMessage;

        $chatMessage->fill([
            'chat_id' => $this->chat->getKey(),
            'user_id' => $this->menti->getKey(),
            'message' => 'Test message',
            'is_read' => false,
            'extra_attribute' => 'value',
        ]);
    })->throws(MassAssignmentException::class);

    it('has a precisely defined cast configuration', function () {
        $reflectionMethod = new ReflectionMethod(ChatMessage::class, 'casts');
        $chatMessage = new ChatMessage;
        $casts = $reflectionMethod->invoke($chatMessage);

        expect($casts)->toBe([
            'chat_id' => 'int',
            'user_id' => 'int',
            'message' => 'string',
            'is_read' => 'boolean',
        ]);
    });

    it('has precisely defined fillable attributes and mass assignment works correctly', function () {
        $data = [
            'chat_id' => $this->chat->getKey(),
            'user_id' => $this->menti->getKey(),
            'message' => 'Test message',
            'is_read' => false,
        ];

        $chatMessage = ChatMessage::factory()->create($data);

        expect($chatMessage->chat_id)->toBe($this->chat->getKey())
            ->and($chatMessage->user_id)->toBe($this->menti->getKey())
            ->and($chatMessage->message)->toBe('Test message')
            ->and($chatMessage->is_read)->toBe(false)
            ->and($chatMessage->chat)->toBeInstanceOf(Chat::class)
            ->and($chatMessage->chat->getKey())->toBe($this->chat->getKey());
    });

});
