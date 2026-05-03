<?php

declare(strict_types=1);

use App\Actions\Chat\SetBan;
use App\Enums\ChatStatusEnum;
use App\Models\Chat;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Event;
use Symfony\Component\HttpFoundation\Response;

mutates(SetBan::class);

describe('SetBan', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        Event::fake();
        Date::setTestNow(Illuminate\Support\Facades\Date::parse('2026-02-01 12:00:00'));

        $this->user = User::factory()->create();
        $this->user->profile()->create([
            'name'      => 'profile name 1',
            'last_name' => 'profile last_name 1',
        ]);
        $this->chat = Chat::factory()->create();
        $this->user->chats()->attach($this->chat->id, [
            'status' => ChatStatusEnum::ACTIVE->value,
        ]);
        Auth::login($this->user);
        request()->setUserResolver(fn () => $this->user);
    });

    it('sets the chat status to BANNED when ban parameter is true', function (): void {
        $response = $this->postJson(route('chat.set-ban', $this->chat), ['ban' => 1]);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson(['ban' => true]);

        $this->assertDatabaseHas('chat_users', [
            'chat_id' => $this->chat->id,
            'user_id' => $this->user->id,
            'status'  => ChatStatusEnum::BANNED->value,
        ]);
    });

    it('sets the chat status to ACTIVE when ban parameter is false or missing', function (): void {
        // Спочатку забанимо чат вручную в БД
        $this->user->chats()->updateExistingPivot($this->chat->id, [
            'status' => ChatStatusEnum::BANNED->value,
        ]);

        // Відправляємо запит на розбан (ban = 0)
        $response = $this->postJson(route('chat.set-ban', $this->chat));

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson(['ban' => false]);

        $this->assertDatabaseHas('chat_users', [
            'chat_id' => $this->chat->id,
            'user_id' => $this->user->id,
            'status'  => ChatStatusEnum::ACTIVE->value,
        ]);
    });

    it('defaults to ACTIVE status if ban parameter is not provided', function (): void {
        // Встановимо статус BANNED
        $this->user->chats()->updateExistingPivot($this->chat->id, [
            'status' => ChatStatusEnum::BANNED->value,
        ]);

        // Робимо запит без параметрів
        $response = $this->postJson(route('chat.set-ban', $this->chat));

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson(['ban' => false]);

        expect($this->user->chats()->first()->pivot->status)
            ->toBe(ChatStatusEnum::ACTIVE->value);
    });
});
