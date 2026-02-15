<?php

declare(strict_types=1);

use App\Actions\Chat\CreateChat;
use App\Actions\Chat\SendMessage;
use App\Enums\ChatStatusEnum;
use App\Models\Chat;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

mutates(CreateChat::class);

describe('CreateChat', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        Date::setTestNow(Illuminate\Support\Facades\Date::parse('2026-02-01 12:00:00'));
        $this->owner = User::factory()->create();
        $this->owner->profile()->create([
            'name'      => 'profile name 1',
            'last_name' => 'profile last_name 1',
        ]);
        Auth::login($this->owner);
        request()->setUserResolver(fn () => $this->owner);
    });

    it('creates a new chat and calls SendMessage if chat does not exist', function (): void {
        $companion = User::factory()->create();
        $companion->profile()->update([
            'name'      => 'profile companion',
            'last_name' => 'profile last_companion',
        ]);

        $payload = [
            'message' => 'Hello, this is a first message',
            'files'   => [],
        ];

        SendMessage::mock()
            ->shouldReceive('handle')
            ->once()
            ->with(
                Mockery::type(Chat::class),
                Mockery::on(fn ($request): bool => $request->message === $payload['message'])
            )
            ->andReturn(response()->json(['status' => 'success_from_mock']));

        $response = $this->postJson(route('chat.create', $companion), $payload);
        $response->assertStatus(Response::HTTP_OK)
            ->assertJson(['status' => 'success_from_mock']);

        $this->assertDatabaseHas('chats', [
            'name' => 'profile companion',
        ]);

        $chat = Chat::query()->first();

        expect($chat->users)->toHaveCount(2)
            ->and($chat->users->where('id', $this->owner->id)->first()->pivot->status)
            ->toBe(ChatStatusEnum::ACTIVE->value)
            ->and($chat->users->where('id', $companion->id)->first()->pivot->status)
            ->toBe(ChatStatusEnum::ACTIVE->value);
    });

    it('validates the request fields', function (): void {
        $companion = User::factory()->create();
        $companion->profile()->update([
            'name'      => 'profile companion',
            'last_name' => 'profile last_companion',
        ]);

        $response = $this->postJson(route('chat.create', $companion), [
            'message' => '',
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['message']);
    });
});
