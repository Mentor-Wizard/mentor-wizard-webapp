<?php

declare(strict_types=1);

use App\Enums\ChatStatusEnum;
use App\Models\Chat;
use App\Models\ChatMessage;
use App\Models\User;
use App\Policies\ChatMessagesPolicy;
use Database\Seeders\RoleSeeder;

describe('ChatMessagesPolicy', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        $this->user = User::factory()->create();
        $this->user->profile()->create([
            'name'      => 'profile name 1',
            'last_name' => 'profile last_name 1',
        ]);
        $this->policy = new ChatMessagesPolicy;
    });

    it('denies viewAny for everyone', function (): void {
        expect($this->policy->viewAny($this->user))->toBeFalse();
    });

    it('allows create for everyone', function (): void {
        expect($this->policy->create($this->user))->toBeTrue();
    });

    describe('view', function (): void {
        it('allows the author to view their own message', function (): void {
            $message = ChatMessage::factory()->create(['user_id' => $this->user->id]);

            expect($this->policy->view($this->user, $message))->toBeTrue();
        });

        it('allows the companion in the chat to view the message', function (): void {
            $companion = User::factory()->create();

            $chat = Chat::factory()->create();

            $chat->users()->attach([
                $this->user->id => ['status' => ChatStatusEnum::ACTIVE->value],
                $companion->id  => ['status' => ChatStatusEnum::ACTIVE->value],
            ]);

            $message = ChatMessage::factory()->create([
                'chat_id' => $chat->id,
                'user_id' => $companion->id,
            ]);

            expect($this->policy->view($this->user, $message))->toBeTrue();
        });

        it('denies a stranger from viewing a message', function (): void {
            $stranger = User::factory()->create();
            $companion = User::factory()->create();

            $chat = Chat::factory()->create();

            $chat->users()->attach([
                $this->user->id => ['status' => ChatStatusEnum::ACTIVE->value],
                $companion->id  => ['status' => ChatStatusEnum::ACTIVE->value],
            ]);

            $message = ChatMessage::factory()->create([
                'chat_id' => $chat->id,
                'user_id' => $companion->id,
            ]);

            expect($this->policy->view($stranger, $message))->toBeFalse();
        });
    });

    describe('update', function (): void {
        it('allows the author to update their message', function (): void {
            $user = User::factory()->create();
            $message = ChatMessage::factory()->create(['user_id' => $user->id]);

            expect($this->policy->update($user, $message))->toBeTrue();
        });

        it('denies others (even companions) from updating the message', function (): void {
            $user = User::factory()->create();
            $companion = User::factory()->create();

            $message = ChatMessage::factory()->create(['user_id' => $user->id]);

            expect($this->policy->update($companion, $message))->toBeFalse();
        });
    });
});
