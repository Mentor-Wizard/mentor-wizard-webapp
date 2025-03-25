<?php

declare(strict_types=1);

use App\Models\Chat;
use App\Models\ChatMessage;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\Eloquent\MassAssignmentException;

covers(Chat::class);

describe('Chat Model', function () {
    beforeEach(function () {
        $this->seed(RoleSeeder::class);
        $this->mentor = User::factory()->create();
        $this->menti = User::factory()->create();
        $this->coach = User::factory()->create();
    });

    it('can create chat with basic attributes with relations', function () {
        $chat = Chat::factory()->create([
            'mentor_id' => $this->mentor->getKey(),
            'menti_id' => $this->menti->getKey(),
            'coach_id' => $this->coach->getKey(),
        ]);

        expect($chat)->toBeInstanceOf(Chat::class)
            ->and($chat->mentor_id)->toBe($this->mentor->getKey())
            ->and($chat->menti_id)->toBe($this->menti->getKey())
            ->and($chat->coach_id)->toBe($this->coach->getKey())
            ->and($chat->mentor)->toBeInstanceOf(User::class)
            ->and($chat->mentor->getKey())->toBe($this->mentor->getKey())
            ->and($chat->menti)->toBeInstanceOf(User::class)
            ->and($chat->menti->getKey())->toBe($this->menti->getKey())
            ->and($chat->coach)->toBeInstanceOf(User::class)
            ->and($chat->coach->getKey())->toBe($this->coach->getKey());
    });

    it('can create chat with basic attributes and casts are correct', function () {
        $chat = Chat::factory()->create([
            'mentor_id' => (string) $this->mentor->getKey(),
            'menti_id' => (string) $this->menti->getKey(),
            'coach_id' => (string) $this->coach->getKey(),
        ]);

        expect($chat)->toBeInstanceOf(Chat::class)
            ->and($chat->mentor_id)->toBeInt()
            ->and($chat->menti_id)->toBeInt()
            ->and($chat->coach_id)->toBeInt();
    });

    it('cascades on mentor deletion', function () {
        $chat = Chat::factory()->create([
            'mentor_id' => $this->mentor->getKey(),
            'menti_id' => $this->menti->getKey(),
            'coach_id' => $this->coach->getKey(),
        ]);

        $this->mentor->delete();

        $this->assertDatabaseHas('chats', [
            'id' => $chat->getKey(),
            'mentor_id' => null,
        ]);
    });

    it('cascades on menti deletion', function () {
        $chat = Chat::factory()->create([
            'mentor_id' => $this->mentor->getKey(),
            'menti_id' => $this->menti->getKey(),
            'coach_id' => $this->coach->getKey(),
        ]);

        $this->menti->delete();

        $this->assertDatabaseHas('chats', [
            'id' => $chat->getKey(),
            'menti_id' => null,
        ]);
    });

    it('cascades on coach deletion', function () {
        $chat = Chat::factory()->create([
            'mentor_id' => $this->mentor->getKey(),
            'menti_id' => $this->menti->getKey(),
            'coach_id' => $this->coach->getKey(),
        ]);

        $this->coach->delete();

        $this->assertDatabaseHas('chats', [
            'id' => $chat->getKey(),
            'coach_id' => null,
        ]);
    });

    it('deletes chat when all user IDs are NULL after saving model', function () {
        $chat = Chat::factory()->create([
            'menti_id' => null,
            'mentor_id' => null,
            'coach_id' => null,
        ]);

        $chat->save();

        expect($chat->exists())->toBeFalse();
    });

    it('deletes chat when all user IDs are NULL after updadting model', function () {
        $chat = Chat::factory()->create([
            'mentor_id' => $this->mentor->getKey(),
            'menti_id' => $this->menti->getKey(),
            'coach_id' => $this->coach->getKey(),
        ]);

        $chat->update([
            'menti_id' => null,
            'mentor_id' => null,
            'coach_id' => null,
        ]);

        $chat->save();

        expect($chat->exists())->toBeFalse();
    });

    it('does not delete chat when not all user IDs are NULL', function () {
        $chat = Chat::factory()->create([
            'mentor_id' => $this->mentor->getKey(),
            'menti_id' => $this->menti->getKey(),
            'coach_id' => $this->coach->getKey(),
        ]);

        $chat->update([
            'coach_id' => null,
        ]);

        $chat->save();

        expect($chat->exists())->toBeTrue();
    });

    it('has correctly defined fillable attributes', function () {
        $chat = new Chat;

        expect($chat->getFillable())->toBe([
            'mentor_id',
            'menti_id',
            'coach_id',
        ]);
    });

    it('throws an exception when mass assigning unauthorized attributes', function () {
        $chat = new Chat;

        $chat->fill([
            'mentor_id' => $this->mentor->getKey(),
            'menti_id' => $this->menti->getKey(),
            'coach_id' => $this->coach->getKey(),
            'extra_field' => 'unexpected',
        ]);
    })->throws(MassAssignmentException::class);

    it('has a precisely defined cast configuration', function () {
        $reflectionMethod = new ReflectionMethod(Chat::class, 'casts');
        $chat = new Chat;
        $casts = $reflectionMethod->invoke($chat);

        expect($casts)->toBe([
            'mentor_id' => 'int',
            'menti_id' => 'int',
            'coach_id' => 'int',
        ]);
    });

    it('has precisely defined fillable attributes and mass assignment works correctly', function () {
        $data = [
            'mentor_id' => $this->mentor->getKey(),
            'menti_id' => $this->menti->getKey(),
            'coach_id' => $this->coach->getKey(),
        ];

        $chat = Chat::factory()->create($data);

        expect($chat->mentor_id)->toBe($this->mentor->getKey())
            ->and($chat->menti_id)->toBe($this->menti->getKey())
            ->and($chat->coach_id)->toBe($this->coach->getKey())
            ->and($chat->mentor)->toBeInstanceOf(User::class)
            ->and($chat->mentor->getKey())->toBe($this->mentor->getKey())
            ->and($chat->menti)->toBeInstanceOf(User::class)
            ->and($chat->menti->getKey())->toBe($this->menti->getKey())
            ->and($chat->coach)->toBeInstanceOf(User::class)
            ->and($chat->coach->getKey())->toBe($this->coach->getKey());

        Chat::create(array_merge($data, ['extra_field' => 'unexpected']));
    })->throws(MassAssignmentException::class);

    it('has a valid messages relation', function () {
        $chat = Chat::factory()->create([
            'mentor_id' => $this->mentor->getKey(),
            'menti_id' => $this->menti->getKey(),
            'coach_id' => $this->coach->getKey(),
        ]);

        $messages = ChatMessage::factory()->create([
            'chat_id' => $chat->getKey(),
            'user_id' => $this->menti->getKey(),
            'message' => 'Test message',
            'is_read' => false,
        ]);

        expect($chat->messages->first())->toBeInstanceOf(ChatMessage::class)
            ->and($chat->messages->first()->getKey())->toBe($messages->first()->getKey());
    });
});
