<?php

declare(strict_types=1);

use App\Actions\Chat\ChatMessages;
use App\Events\Chats\UnreadMessagesEvent;
use App\Models\Chat;
use App\Models\ChatMessage;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Symfony\Component\HttpFoundation\Response;

mutates(ChatMessages::class);

describe('ChatMessages', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        Event::fake();
        Date::setTestNow(Illuminate\Support\Facades\Date::parse('2026-02-01 12:00:00'));
    });

    it('returns messages and files, marks messages as read and dispatches unread event', function (): void {
        $user = User::factory()->create();
        $user->profile()->create([
            'name'      => 'profile name 1',
            'last_name' => 'profile last_name 1',
        ]);
        Auth::login($user);
        request()->setUserResolver(fn () => $user);
        $chat = Chat::factory()->create();

        ChatMessage::factory()->create([
            'chat_id' => $chat->id,
            'user_id' => $user->id,
            'is_read' => false,
            'message' => 'Hello!',
        ]);

        /** @var ChatMessages $action */
        $action = resolve(ChatMessages::class);
        $result = $action->handle(request()->setUserResolver(fn (): object => clone $user), $chat);

        expect($result)->toBeInstanceOf(JsonResponse::class)
            ->and($result->getStatusCode())->toBe(Response::HTTP_OK);

        $data = $result->getData(true);
        expect($data)->toHaveKeys(['messages', 'files'])
            ->and($data['messages'])->not->toBeEmpty()
            ->and($data['messages'][0]['message'])->toBe('Hello!')
            ->and(ChatMessage::query()->where('chat_id', $chat->id)->where('is_read', false)->count())
            ->toBe(0);

        Event::assertDispatched(fn (UnreadMessagesEvent $event): bool => $event->user->id === $user->id);
    });

    it('returns files when messages have attachments', function (): void {
        $user = User::factory()->create();
        $user->profile()->create([
            'name'      => 'profile name 1',
            'last_name' => 'profile last_name 1',
        ]);
        Auth::login($user);
        request()->setUserResolver(fn () => $user);
        $chat = Chat::factory()->create();

        $message = ChatMessage::factory()->create([
            'chat_id' => $chat->id,
            'user_id' => $user->id,
            'is_read' => false,
            'message' => 'Hello with file!',
        ]);

        $message->addMedia(UploadedFile::fake()->image('test.jpg'))->toMediaCollection('files');

        /** @var ChatMessages $action */
        $action = resolve(ChatMessages::class);
        $result = $action->handle(request()->setUserResolver(fn (): object => clone $user), $chat);

        $data = $result->getData(true);
        expect($data['files'])->not->toBeEmpty();
    });

    it('returns empty lists if no messages exist', function (): void {
        $user = User::factory()->create();
        Auth::login($user);
        $chat = Chat::factory()->create();

        $action = resolve(ChatMessages::class);
        $result = $action->handle(request()->setUserResolver(fn (): object => clone $user), $chat);

        expect($result->getData(true))->toBe([
            'messages' => [],
            'files'    => [],
        ]);
    });
});
