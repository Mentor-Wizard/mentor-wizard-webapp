<?php

declare(strict_types=1);

use App\Actions\Chat\ChatListUser;
use App\Enums\ChatStatusEnum;
use App\Enums\RoleEnum;
use App\Events\Chats\UnreadMessagesEvent;
use App\Models\Chat;
use App\Models\ChatMessage;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\Response;

mutates(ChatListUser::class);

describe('ChatListUser', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        Event::fake();
        Date::setTestNow(Illuminate\Support\Facades\Date::parse('2026-02-01 12:00:00'));
    });

    it('empty list users with chat', function (): void {
        $user = User::factory()->create();
        Auth::login($user);

        $action = new ChatListUser;
        $result = $action->handle();

        expect($result)->toBeInstanceOf(JsonResponse::class)
            ->and($result->getStatusCode())->toBe(Response::HTTP_OK)
            ->and($result->getData(true))->toBe([
                'users' => [],
            ]);
    });

    it('not empty list users with chat ACTIVE', function (): void {
        $user = User::factory()->create([
            'username' => 'user 1',
        ]);
        $user->profile()->create([
            'name'      => 'profile name 1',
            'last_name' => 'profile last_name 1',
        ]);
        $companion = User::factory()->create([
            'username' => 'user 2',
        ]);
        $companion->profile()->create([
            'name'        => 'profile name 2',
            'last_name'   => 'profile last_name 2',
        ]);
        Auth::login($user);
        $chat = Chat::factory()->create();
        $chat->users()->attach($user->id, ['status' => ChatStatusEnum::ACTIVE->value, 'is_muted' => false]);
        $chat->users()->attach($companion->id, ['status' => ChatStatusEnum::ACTIVE->value, 'is_muted' => false]);

        ChatMessage::factory()->create([
            'chat_id'    => $chat->id,
            'user_id'    => $user->id,
            'message'    => 'test 1',
            'is_read'    => false,
            'created_at' => '2026-02-01 12:00:00',
            'updated_at' => '2026-02-01 12:00:00',
        ]);
        ChatMessage::factory()->create([
            'chat_id'    => $chat->id,
            'user_id'    => $companion->id,
            'message'    => 'test 2',
            'is_read'    => false,
            'created_at' => '2026-02-01 12:10:00',
            'updated_at' => '2026-02-01 12:10:00',
        ]);

        $action = new ChatListUser;
        $result = $action->handle();

        expect($result)
            ->toBeInstanceOf(JsonResponse::class)
            ->and($result->getStatusCode())->toBe(Response::HTTP_OK)
            ->and($result->getData(true))->toMatchArray([
                'users' => [
                    [
                        'id'        => $companion->id,
                        'chatId'    => $chat->id,
                        'name'      => 'profile name 2 profile last_name 2',
                        'avatar'    => 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80',
                        'slug'      => null,
                        'message'   => 'test 2',
                        'online'    => false,
                        'isMuted'   => false,
                        'ban'       => false,
                        'canSend'   => true,
                        'isRead'    => false,
                        'createdAt' => '01.02.2026',
                        'tags'      => null,
                        'last'      => '10 minutes from now',
                    ],
                ],
            ]);

        Event::assertDispatched(UnreadMessagesEvent::class, fn ($event): bool => $event->user['id'] === $user->id
            && $event->notificationsCount === 1
            && $event->socket === null);
    });

    it('not empty list users with chat BANNED', function (): void {
        $user = User::factory()->create([
            'username' => 'user 1',
            'slug'     => 'slug 1',
        ]);
        $user->profile()->create([
            'name'      => 'profile name 1',
            'last_name' => 'profile last_name 1',
        ]);
        $user->assignRole(Role::findByName(RoleEnum::MENTOR->value));
        $companion = User::factory()->create([
            'username' => 'user 2',
            'slug'     => 'slug 2',
        ]);
        $companion->profile()->create([
            'name'        => 'profile name 2',
            'last_name'   => 'profile last_name 2',
        ]);
        $companion->assignRole(Role::findByName(RoleEnum::MENTOR->value));
        Auth::login($user);
        $chat = Chat::factory()->create();
        $chat->users()->attach($user->id, ['status' => ChatStatusEnum::BANNED->value, 'is_muted' => false]);
        $chat->users()->attach($companion->id, ['status' => ChatStatusEnum::BANNED->value, 'is_muted' => false]);

        ChatMessage::factory()->create([
            'chat_id'    => $chat->id,
            'user_id'    => $user->id,
            'message'    => 'test 1',
            'is_read'    => false,
            'created_at' => '2026-02-01 12:00:00',
            'updated_at' => '2026-02-01 12:00:00',
        ]);
        ChatMessage::factory()->create([
            'chat_id'    => $chat->id,
            'user_id'    => $companion->id,
            'message'    => 'test 2',
            'is_read'    => false,
            'created_at' => '2026-02-01 12:10:00',
            'updated_at' => '2026-02-01 12:10:00',
        ]);

        $action = new ChatListUser;
        $result = $action->handle();

        expect($result)
            ->toBeInstanceOf(JsonResponse::class)
            ->and($result->getStatusCode())->toBe(Response::HTTP_OK)
            ->and($result->getData(true))->toMatchArray([
                'users' => [
                    [
                        'id'        => $companion->id,
                        'chatId'    => $chat->id,
                        'name'      => 'profile name 2 profile last_name 2',
                        'avatar'    => 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80',
                        'slug'      => 'slug 2',
                        'message'   => 'test 2',
                        'online'    => false,
                        'isMuted'   => false,
                        'ban'       => true,
                        'canSend'   => false,
                        'isRead'    => false,
                        'createdAt' => '01.02.2026',
                        'tags'      => null,
                        'last'      => '10 minutes from now',
                    ],
                ],
            ]);

        Event::assertDispatched(UnreadMessagesEvent::class, fn ($event): bool => $event->user['id'] === $user->id
            && $event->notificationsCount === 1
            && $event->socket === null);
    });

    it('not empty list users with chat BANNED without messages', function (): void {
        $user = User::factory()->create([
            'username' => 'user 1',
            'slug'     => 'slug 1',
        ]);
        $user->profile()->create([
            'name'      => 'profile name 1',
            'last_name' => 'profile last_name 1',
        ]);
        $user->assignRole(Role::findByName(RoleEnum::MENTOR->value));
        $companion = User::factory()->create([
            'username' => 'user 2',
            'slug'     => 'slug 2',
        ]);
        $companion->profile()->create([
            'name'        => 'profile name 2',
            'last_name'   => 'profile last_name 2',
        ]);
        $companion->assignRole(Role::findByName(RoleEnum::MENTOR->value));
        Auth::login($user);
        $chat = Chat::factory()->create();
        $chat->users()->attach($user->id, ['status' => ChatStatusEnum::BANNED->value, 'is_muted' => false]);
        $chat->users()->attach($companion->id, ['status' => ChatStatusEnum::BANNED->value, 'is_muted' => false]);

        $action = new ChatListUser;
        $result = $action->handle();

        expect($result)
            ->toBeInstanceOf(JsonResponse::class)
            ->and($result->getStatusCode())->toBe(Response::HTTP_OK)
            ->and($result->getData(true))->toMatchArray([
                'users' => [
                    [
                        'id'        => $companion->id,
                        'chatId'    => $chat->id,
                        'name'      => 'profile name 2 profile last_name 2',
                        'avatar'    => 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80',
                        'slug'      => 'slug 2',
                        'message'   => null,
                        'online'    => false,
                        'isMuted'   => false,
                        'ban'       => true,
                        'canSend'   => false,
                        'isRead'    => null,
                        'createdAt' => null,
                        'tags'      => null,
                        'last'      => null,
                    ],
                ],
            ]);

    });

    it('gets last message', function (): void {
        $chat = Chat::factory()->create();
        $message1 = ChatMessage::factory()->create(['chat_id' => $chat->id]);
        $message2 = ChatMessage::factory()->create(['chat_id' => $chat->id]);

        $action = new ChatListUser;

        $reflection = new ReflectionClass(ChatListUser::class);
        $method = $reflection->getMethod('getLastMessage');

        $lastMessage = $method->invoke($action, $chat);

        expect($lastMessage->id)->toBe($message2->id);
    });

    it('returns human readable diff', function (): void {
        $action = new ChatListUser;

        $reflection = new ReflectionClass(ChatListUser::class);
        $method = $reflection->getMethod('getLastDateInfo');

        $date = Illuminate\Support\Facades\Date::parse('2026-02-01 12:00:00');
        Date::setTestNow($date);

        $result = $method->invoke($action, $date);

        expect($result)->toBe('0 seconds ago'); // Carbon::diffForHumans()
    });
});
