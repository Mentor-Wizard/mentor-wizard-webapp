<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Auth;
use Modules\Chat\Actions\CreateChat;
use Modules\Chat\Actions\SendMessage;
use Modules\Chat\Enums\ChatStatusEnum;
use Modules\Chat\Models\Chat;
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
            ->and($chat->users->where('id', $this->owner->id)->first()->pivot->is_muted)
            ->toBeFalse()
            ->and($chat->users->where('id', $companion->id)->first()->pivot->status)
            ->toBe(ChatStatusEnum::ACTIVE->value)
            ->and($chat->users->where('id', $companion->id)->first()->pivot->is_muted)
            ->toBeFalse();
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

    it('returns existing chat and calls SendMessage if chat already exists and BANNED', function (): void {
        $companion = User::factory()->create();
        $companion->profile()->update(['name' => 'Existing Companion']);

        $chat = Chat::factory()->create(['name' => 'Old Name']);

        $this->owner->chats()->attach($chat->id, ['status' => ChatStatusEnum::BANNED->value]);
        $companion->chats()->attach($chat->id, ['status' => ChatStatusEnum::BANNED->value]);

        $payload = [
            'message' => 'Message to existing chat',
            'files'   => [],
        ];

        $response = $this->postJson(route('chat.create', $companion), $payload);

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    });

    it('returns existing chat and calls SendMessage if chat already exists and ACTIVE', function (): void {
        $companion = User::factory()->create();
        $companion->profile()->update(['name' => 'Existing Companion']);

        // Створюємо існуючий чат
        $chat = Chat::factory()->create(['name' => 'Existing Chat']);

        // Прив'язуємо користувачів (як у вашому коді через whereHas)
        $chat->users()->attach([
            $this->owner->id => ['status' => ChatStatusEnum::ACTIVE->value],
            $companion->id   => ['status' => ChatStatusEnum::ACTIVE->value],
        ]);

        $payload = [
            'message' => 'Message to existing chat',
            'files'   => [],
        ];

        SendMessage::mock()
            ->shouldReceive('handle')
            ->once()
            ->with(
                Mockery::on(fn ($passedChat): bool => $passedChat->id === $chat->id),
                Mockery::any()
            )
            ->andReturn(response()->json(['status' => 'success_from_existing_mock']));

        $response = $this->postJson(route('chat.create', $companion), $payload);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson(['status' => 'success_from_existing_mock']);

        expect(Chat::query()->count())->toBe(1);
    });

    it('ensures it searches for the SPECIFIC companion chat', function (): void {
        $stranger = User::factory()->create();
        $wrongChat = Chat::factory()->create(['name' => 'Wrong Chat']);
        $wrongChat->users()->attach([
            $this->owner->id => ['status' => ChatStatusEnum::ACTIVE->value],
            $stranger->id    => ['status' => ChatStatusEnum::ACTIVE->value],
        ]);

        $companion = User::factory()->create();
        $companion->profile()->updateOrCreate(
            ['user_id' => $companion->id],
            ['name' => 'Specific Companion']
        );
        $companion->refresh();

        SendMessage::mock()
            ->shouldReceive('handle')
            ->once()
            ->with(
                Mockery::on(fn ($chat): bool => $chat->name === 'Specific Companion'),
                Mockery::any()
            )
            ->andReturn(response()->json(['status' => 'created_new']));

        $response = $this->postJson(route('chat.create', $companion), ['message' => 'Hi']);

        $response->assertStatus(200)
            ->assertJson(['status' => 'created_new']);
    });

    it('ensures throw_if stops execution on BANNED status', function (ChatStatusEnum $ownerStatus, ChatStatusEnum $companionStatus): void {
        $companion = User::factory()->create();
        $chat = Chat::factory()->create();

        $chat->users()->attach([
            $this->owner->id => ['status' => $ownerStatus->value],
            $companion->id   => ['status' => $companionStatus->value],
        ]);

        SendMessage::mock()->shouldNotReceive('handle');

        $response = $this->postJson(route('chat.create', $companion), [
            'message' => 'Checking ban status',
        ]);

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    })->with([
        'owner is banned'     => [ChatStatusEnum::BANNED, ChatStatusEnum::ACTIVE],
        'companion is banned' => [ChatStatusEnum::ACTIVE, ChatStatusEnum::BANNED],
        'both are banned'     => [ChatStatusEnum::BANNED, ChatStatusEnum::BANNED],
    ]);
});
