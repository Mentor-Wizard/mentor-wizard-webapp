<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Modules\Chat\Enums\ChatStatusEnum;
use Modules\Chat\Models\Chat;
use Modules\Chat\Policies\ChatPolicy;

mutates(ChatPolicy::class);

describe('ChatPolicy', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        $this->user = User::factory()->create();
        $this->policy = new ChatPolicy;
    });

    describe('view', function (): void {
        it('allows a user to view their own chat', function (): void {
            $chat = Chat::factory()->create();

            $chat->users()->attach($this->user->id, [
                'status' => ChatStatusEnum::ACTIVE->value,
            ]);

            expect($this->policy->view($this->user, $chat))->toBeTrue();
        });

        it('denies a stranger from viewing a chat they are not part of', function (): void {
            $chat = Chat::factory()->create();
            $otherUser = User::factory()->create();

            $chat->users()->attach($otherUser->id, [
                'status' => ChatStatusEnum::ACTIVE->value,
            ]);

            expect($this->policy->view($this->user, $chat))->toBeFalse();
        });

        it('allows view when user is one of multiple participants', function (): void {
            $user2 = User::factory()->create();
            $user3 = User::factory()->create();

            $chat = Chat::factory()->create();

            $chat->users()->attach([
                $this->user->id => ['status' => ChatStatusEnum::ACTIVE->value],
                $user2->id      => ['status' => ChatStatusEnum::ACTIVE->value],
                $user3->id      => ['status' => ChatStatusEnum::ACTIVE->value],
            ]);

            expect($this->policy->view($this->user, $chat))->toBeTrue();
        });
    });

    describe('update', function (): void {
        it('allows a user to update their own chat', function (): void {
            $chat = Chat::factory()->create();

            $chat->users()->attach($this->user->id, [
                'status' => ChatStatusEnum::ACTIVE->value,
            ]);

            expect($this->policy->update($this->user, $chat))->toBeTrue();
        });

        it('denies a stranger from updating a chat they are not part of', function (): void {
            $chat = Chat::factory()->create();
            $otherUser = User::factory()->create();

            $chat->users()->attach($otherUser->id, [
                'status' => ChatStatusEnum::ACTIVE->value,
            ]);

            expect($this->policy->update($this->user, $chat))->toBeFalse();
        });

        it('allows update when user is one of multiple participants', function (): void {
            $user2 = User::factory()->create();

            $chat = Chat::factory()->create();

            $chat->users()->attach([
                $this->user->id => ['status' => ChatStatusEnum::ACTIVE->value],
                $user2->id      => ['status' => ChatStatusEnum::ACTIVE->value],
            ]);

            expect($this->policy->update($this->user, $chat))->toBeTrue();
        });
    });
});
