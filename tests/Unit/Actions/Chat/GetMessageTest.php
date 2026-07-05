<?php

declare(strict_types=1);

use App\Actions\Chat\GetMessage;
use App\Actions\Chat\UnreadMessages;
use App\Enums\ChatStatusEnum;
use App\Events\Chats\UnreadMessagesEvent;
use App\Models\Chat;
use App\Models\ChatMessage;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Event;
use Symfony\Component\HttpFoundation\Response;

describe('GetMessage', function (): void {
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

    it('marks message as read, dispatches event and returns resource', function (): void {
        $companion = User::factory()->create();
        $companion->profile()->update([
            'name'      => 'profile companion',
            'last_name' => 'profile last_companion',
        ]);
        $chat = Chat::factory()->create();

        $chat->users()->attach([
            $this->owner->id => ['status' => ChatStatusEnum::ACTIVE->value, 'is_muted' => false],
            $companion->id   => ['status' => ChatStatusEnum::ACTIVE->value, 'is_muted' => false],
        ]);

        $message = ChatMessage::factory()->create([
            'chat_id' => $chat->id,
            'user_id' => $this->owner->id,
            'is_read' => false,
            'message' => 'Secret message',
        ]);

        UnreadMessages::mock()
            ->shouldReceive('handle')
            ->once()
            ->with(Mockery::on(fn ($user): bool => $user->id === $this->owner->id))
            ->andReturn(1);

        $action = resolve(GetMessage::class);
        $result = $action->handle($message);

        expect($result)->toBeInstanceOf(JsonResponse::class)
            ->and($result->getStatusCode())->toBe(Response::HTTP_OK);

        $message->refresh();
        expect($message->is_read)->toBeTrue();

        $data = $result->getData(true);
        expect($data)->toHaveKey('message')
            ->and($data['message']['message'])->toBe('Secret message');

        Event::assertDispatched(fn (UnreadMessagesEvent $event): bool => $event->user->id === $companion->id);
    });
});
