<?php

declare(strict_types=1);

use App\Actions\Chat\SetMute;
use App\Enums\ChatStatusEnum;
use App\Models\Chat;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

mutates(SetMute::class);

describe('SetMute', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        $this->owner = User::factory()->create();
        $this->owner->profile()->create([
            'name'      => 'profile name 1',
            'last_name' => 'profile last_name 1',
        ]);
        Auth::login($this->owner);
    });

    it('successfully updates is_muted status to true', function (): void {
        $chat = Chat::factory()->create();

        $chat->users()->attach($this->owner->id, [
            'status'   => ChatStatusEnum::ACTIVE,
            'is_muted' => false,
        ]);

        $request = new Request(['isMuted' => true]);
        $request->setUserResolver(fn () => $this->owner);

        $action = app(SetMute::class);
        $result = $action->handle($chat, $request);

        expect($result->getStatusCode())->toBe(Response::HTTP_OK)
            ->and($result->getData(true))->toBe(['isMuted' => true]);

        $this->assertDatabaseHas('chat_users', [
            'chat_id'  => $chat->id,
            'user_id'  => $this->owner->id,
            'is_muted' => true,
        ]);
    });

    it('successfully updates is_muted status to false', function (): void {
        // Arrange
        $chat = Chat::factory()->create();
        $chat->users()->attach($this->owner->id, [
            'status'   => ChatStatusEnum::ACTIVE->value,
            'is_muted' => true, // стартуємо з true
        ]);

        $request = new Request(['isMuted' => false]);
        $request->setUserResolver(fn () => $this->owner);

        // Act
        $action = app(SetMute::class);
        $result = $action->handle($chat, $request);

        // Assert
        expect($result->getData(true))->toBe(['isMuted' => false]);

        $this->assertDatabaseHas('chat_users', [
            'chat_id'  => $chat->id,
            'user_id'  => $this->owner->id,
            'is_muted' => false,
        ]);
    });

    it('uses default value false when isMuted is missing in request', function (): void {
        $chat = Chat::factory()->create();
        $chat->users()->attach($this->owner->id, [
            'status'   => ChatStatusEnum::ACTIVE,
            'is_muted' => true, // стартуємо з true, щоб побачити зміну на дефолтний false
        ]);

        // ПУСТИЙ ЗАПИТ (без isMuted)
        $request = new Request([]);
        $request->setUserResolver(fn () => $this->owner);

        $action = app(SetMute::class);
        $result = $action->handle($chat, $request);

        // Перевіряємо, що спрацював дефолт 0, який перетворився на false
        // Це вбиває Increment/Decrement Integer мутації (Line 23, 28)
        expect($result->getData(true))->toBe(['isMuted' => false]);

        $this->assertDatabaseHas('chat_users', [
            'chat_id'  => $chat->id,
            'user_id'  => $this->owner->id,
            'is_muted' => false,
        ]);
    });

    it('returns strictly boolean type even if integer is provided', function (): void {
        $chat = Chat::factory()->create();
        $chat->users()->attach($this->owner->id, ['status' => ChatStatusEnum::ACTIVE]);

        $request = new Request(['isMuted' => 1]);
        $request->setUserResolver(fn () => $this->owner);

        $action = app(SetMute::class);
        $result = $action->handle($chat, $request);

        expect($result->getData(true)['isMuted'])->toBeTrue();
    });
});
