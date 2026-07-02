<?php

declare(strict_types=1);

use App\Enums\ChatStatusEnum;
use App\Models\Chat;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Event;
use Symfony\Component\HttpFoundation\Response;

describe('SetArchive', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        Event::fake();
        Date::setTestNow(Illuminate\Support\Facades\Date::parse('2026-02-01 12:00:00'));

        $this->owner = User::factory()->create();
        $this->owner->profile()->create([
            'name'      => 'profile name 1',
            'last_name' => 'profile last_name 1',
        ]);
        Auth::login($this->owner);
        request()->setUserResolver(fn () => $this->owner);
    });

    it('updates the chat status to archived for the authenticated user', function (): void {
        $chat = Chat::factory()->create();
        $chat->users()->attach($this->owner->id, [
            'status' => ChatStatusEnum::ACTIVE->value,
        ]);

        expect($this->owner->chats()->first()->pivot->status)
            ->toBe(ChatStatusEnum::ACTIVE->value);

        $response = $this->postJson(route('chat.set-archive', $chat));

        $response->assertStatus(Response::HTTP_NO_CONTENT);

        $updatedChat = $this->owner->chats()->where('chat_id', $chat->id)->first();

        expect($updatedChat->pivot->status)->toBe(ChatStatusEnum::ARCHIVED->value);

        $this->assertDatabaseHas('chat_users', [
            'chat_id' => $chat->id,
            'user_id' => $this->owner->id,
            'status'  => ChatStatusEnum::ARCHIVED->value,
        ]);
    });

    it('does not affect other users in the same chat', function (): void {
        $companion = User::factory()->create();
        $chat = Chat::factory()->create();

        $chat->users()->attach([
            $this->owner->id => ['status' => ChatStatusEnum::ACTIVE->value],
            $companion->id   => ['status' => ChatStatusEnum::ACTIVE->value],
        ]);

        $this->postJson(route('chat.set-archive', $chat));

        $companionChat = $companion->chats()->where('chat_id', $chat->id)->first();
        expect($companionChat->pivot->status)->toBe(ChatStatusEnum::ACTIVE->value);
    });
});
