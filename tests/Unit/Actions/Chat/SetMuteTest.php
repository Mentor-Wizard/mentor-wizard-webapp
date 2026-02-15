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
        // Arrange
        $chat = Chat::factory()->create();
        // Спочатку встановлюємо false у півот
        $chat->users()->attach($this->owner->id, [
            'status'   => ChatStatusEnum::ACTIVE,
            'is_muted' => false,
        ]);

        $request = new Request(['isMuted' => true]);
        $request->setUserResolver(fn () => $this->owner);

        // Act
        $action = app(SetMute::class);
        $result = $action->handle($chat, $request);

        // Assert
        expect($result->getStatusCode())->toBe(Response::HTTP_OK)
            ->and($result->getData(true))->toBe(['isMuted' => true]);

        // Перевіряємо базу даних (pivot таблицю)
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
});
