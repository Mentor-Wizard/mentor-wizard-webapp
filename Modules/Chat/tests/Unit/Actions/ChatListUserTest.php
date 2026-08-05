<?php

declare(strict_types=1);

use App\Enums\RoleEnum;
use App\Enums\TagEnum;
use App\Models\MentorProfile;
use App\Models\MentorTag;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Modules\Chat\Actions\ChatListUser;
use Modules\Chat\Enums\ChatStatusEnum;
use Modules\Chat\Events\UnreadMessagesEvent;
use Modules\Chat\Models\Chat;
use Modules\Chat\Models\ChatMessage;
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
        $result = $action->handle(request()->setUserResolver(fn (): object => clone $user));

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
        $result = $action->handle(request()->setUserResolver(fn (): object => clone $user));

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
        $result = $action->handle(request()->setUserResolver(fn (): object => clone $user));

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
        $result = $action->handle(request()->setUserResolver(fn (): object => clone $user));

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

    it('gets last message via eager loading', function (): void {
        $user = User::factory()->create();
        $companion = User::factory()->create();
        $chat = Chat::factory()->create();
        $chat->users()->attach([$user->id, $companion->id], ['status' => ChatStatusEnum::ACTIVE->value, 'is_muted' => false]);

        $message1 = ChatMessage::factory()->create(['chat_id' => $chat->id]);
        $message2 = ChatMessage::factory()->create(['chat_id' => $chat->id]);

        $chat->load(['messages' => static function ($query): void {
            $query->latest('id')->limit(1);
        }]);

        expect($chat->messages->first()?->id)->toBe($message2->id);
    });

    it('returns tags for companion with mentorProfile and STACK type tags', function (): void {
        $user = User::factory()->create();
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
        $companion->assignRole(Role::findByName(RoleEnum::MENTOR->value));

        /** @var MentorProfile $mentorProfile */
        $mentorProfile = MentorProfile::factory()->create(['user_id' => $companion->id]);

        $tag1 = MentorTag::factory()->create([
            'type' => TagEnum::STACK,
            'tag'  => 'PHP',
        ]);
        $tag2 = MentorTag::factory()->create([
            'type' => TagEnum::STACK,
            'tag'  => 'Laravel',
        ]);
        $tag3 = MentorTag::factory()->create([
            'type' => TagEnum::LANGUAGE,
            'tag'  => 'English',
        ]);

        $mentorProfile->mentorTags()->attach([$tag1->id, $tag2->id, $tag3->id]);

        Auth::login($user);
        $chat = Chat::factory()->create();
        $chat->users()->attach($user->id, ['status' => ChatStatusEnum::ACTIVE->value, 'is_muted' => false]);
        $chat->users()->attach($companion->id, ['status' => ChatStatusEnum::ACTIVE->value, 'is_muted' => false]);

        ChatMessage::factory()->create([
            'chat_id'    => $chat->id,
            'user_id'    => $companion->id,
            'message'    => 'test',
            'is_read'    => false,
            'created_at' => '2026-02-01 12:10:00',
            'updated_at' => '2026-02-01 12:10:00',
        ]);

        $action = new ChatListUser;
        $result = $action->handle(request()->setUserResolver(fn (): object => clone $user));

        $resultData = $result->getData(true);

        expect($resultData['users'][0]['tags'])->toBeArray()
            ->toContain('PHP')
            ->toContain('Laravel')
            ->not->toContain('English');
    });

    it('returns null tags when companion has mentorProfile but no STACK tags', function (): void {
        $user = User::factory()->create();
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
        $companion->assignRole(Role::findByName(RoleEnum::MENTOR->value));

        /** @var MentorProfile $mentorProfile */
        $mentorProfile = MentorProfile::factory()->create(['user_id' => $companion->id]);

        $tag = MentorTag::factory()->create([
            'type' => TagEnum::LANGUAGE,
            'tag'  => 'English',
        ]);

        $mentorProfile->mentorTags()->attach($tag->id);

        Auth::login($user);
        $chat = Chat::factory()->create();
        $chat->users()->attach($user->id, ['status' => ChatStatusEnum::ACTIVE->value, 'is_muted' => false]);
        $chat->users()->attach($companion->id, ['status' => ChatStatusEnum::ACTIVE->value, 'is_muted' => false]);

        ChatMessage::factory()->create([
            'chat_id'    => $chat->id,
            'user_id'    => $companion->id,
            'message'    => 'test',
            'is_read'    => false,
            'created_at' => '2026-02-01 12:10:00',
            'updated_at' => '2026-02-01 12:10:00',
        ]);

        $action = new ChatListUser;
        $result = $action->handle(request()->setUserResolver(fn (): object => clone $user));

        $resultData = $result->getData(true);

        expect($resultData['users'][0]['tags'])->toBeNull();
    });

    it('handles chat with no messages correctly', function (): void {
        $user = User::factory()->create();
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

        $action = new ChatListUser;
        $result = $action->handle(request()->setUserResolver(fn (): object => clone $user));

        $resultData = $result->getData(true);

        expect($resultData['users'][0]['message'])->toBeString()->toBeEmpty()->and($resultData['users'][0]['isRead'])->toBeNull()->and($resultData['users'][0]['createdAt'])->toBeNull()->and($resultData['users'][0]['last'])->toBeNull();
    });

    it('handles companion without profile correctly', function (): void {
        $user = User::factory()->create();
        $user->profile()->create([
            'name'      => 'profile name 1',
            'last_name' => 'profile last_name 1',
        ]);
        $companion = User::factory()->create([
            'username' => 'user 2',
        ]);

        Auth::login($user);
        $chat = Chat::factory()->create();
        $chat->users()->attach($user->id, ['status' => ChatStatusEnum::ACTIVE->value, 'is_muted' => false]);
        $chat->users()->attach($companion->id, ['status' => ChatStatusEnum::ACTIVE->value, 'is_muted' => false]);

        ChatMessage::factory()->create([
            'chat_id'    => $chat->id,
            'user_id'    => $companion->id,
            'message'    => 'test',
            'is_read'    => false,
            'created_at' => '2026-02-01 12:10:00',
            'updated_at' => '2026-02-01 12:10:00',
        ]);

        $action = new ChatListUser;
        $result = $action->handle(request()->setUserResolver(fn (): object => clone $user));

        $resultData = $result->getData(true);

        expect($resultData['users'][0]['name'])->toBeNull()
            ->and($resultData['users'][0]['avatar'])->toBeNull();
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

    it('eager loads users relationship to prevent N+1', function (): void {
        $user = User::factory()->create();
        Auth::login($user);

        Chat::factory(3)->create()->each(function ($chat) use ($user): void {
            $companion = User::factory()->create();
            $chat->users()->attach([
                $user->id      => ['status' => ChatStatusEnum::ACTIVE->value],
                $companion->id => ['status' => ChatStatusEnum::ACTIVE->value],
            ]);
        });

        Illuminate\Support\Facades\DB::enableQueryLog();

        (new ChatListUser)->handle(request()->setUserResolver(fn (): object => clone $user));

        $queries = Illuminate\Support\Facades\DB::getQueryLog();

        expect(count($queries))->toBeLessThan(10);
    });

    it('loads only the latest message to avoid overfetching', function (): void {
        $user = User::factory()->create();
        $companion = User::factory()->create();
        Auth::login($user);

        $chat = Chat::factory()->create();
        $chat->users()->attach([
            $user->id      => ['status' => ChatStatusEnum::ACTIVE->value],
            $companion->id => ['status' => ChatStatusEnum::ACTIVE->value],
        ]);

        ChatMessage::factory()->create(['chat_id' => $chat->id, 'id' => 100]);
        ChatMessage::factory()->create(['chat_id' => $chat->id, 'id' => 101]);
        ChatMessage::factory()->create(['chat_id' => $chat->id, 'id' => 102]);

        $action = new ChatListUser;

        $result = $action->handle(request()->setUserResolver(fn (): object => clone $user));

        DB::enableQueryLog();
        (new ChatListUser)->handle(request()->setUserResolver(fn (): object => clone $user));
        $log = DB::getQueryLog();

        $messageQuery = collect($log)->first(fn ($q): bool => str_contains((string) $q['query'], 'chat_messages'));

        expect($messageQuery['query'])->toContain('"laravel_row" <= 1');
    });

    it('does not crash when chat has no companion', function (): void {
        $user = User::factory()->create();
        Auth::login($user);

        $chat = Chat::factory()->create();

        $chat->users()->attach($user->id, [
            'status'   => ChatStatusEnum::ACTIVE->value,
            'is_muted' => false,
        ]);

        $action = new ChatListUser;

        $result = $action->handle(request()->setUserResolver(fn (): object => clone $user));

        expect($result->getStatusCode())->toBe(Response::HTTP_OK);

        $data = $result->getData(true);
        expect($data['users'])->toBeArray();
    });

    it('kills null-safe mutation for lastMessage message', function (): void {
        $user = User::factory()->create();
        $companion = User::factory()->create();
        Auth::login($user);

        $chat = Chat::factory()->create();
        $chat->users()->attach([
            $user->id      => ['status' => ChatStatusEnum::ACTIVE->value],
            $companion->id => ['status' => ChatStatusEnum::ACTIVE->value],
        ]);

        $action = new ChatListUser;
        $result = $action->handle(request()->setUserResolver(fn (): object => clone $user));

        expect($result->getStatusCode())->toBe(Response::HTTP_OK);

        $data = $result->getData(true);
        expect($data['users'][0]['message'])->toBeEmpty();
    });

    it('kills null-safe mutation for messages in empty chat', function (): void {
        $user = User::factory()->create();
        $companion = User::factory()->create();
        Auth::login($user);

        $chat = Chat::factory()->create();
        $chat->users()->attach([
            $user->id      => ['status' => ChatStatusEnum::ACTIVE->value, 'is_muted' => false],
            $companion->id => ['status' => ChatStatusEnum::ACTIVE->value, 'is_muted' => false],
        ]);

        $action = new ChatListUser;
        $result = $action->handle(request()->setUserResolver(fn (): object => clone $user));

        expect($result->getStatusCode())->toBe(Response::HTTP_OK);

        $data = $result->getData(true);

        expect($data['users'][0]['message'])->toBeEmpty()
            ->and($data['users'][0]['isRead'])->toBeNull();
    });

    it('does not crash and returns empty message when chat has no messages', function (): void {
        $user = User::factory()->create();
        $companion = User::factory()->create();
        Auth::login($user);

        $chat = Chat::factory()->create();
        $chat->users()->attach([
            $user->id      => ['status' => ChatStatusEnum::ACTIVE->value, 'is_muted' => false],
            $companion->id => ['status' => ChatStatusEnum::ACTIVE->value, 'is_muted' => false],
        ]);

        $action = new ChatListUser;

        $result = $action->handle(request()->setUserResolver(fn (): object => clone $user));

        expect($result->getStatusCode())->toBe(Response::HTTP_OK);

        $data = $result->getData(true);

        expect($data['users'][0]['message'])->toBeEmpty()
            ->and($data['users'][0]['isRead'])->toBeNull()
            ->and($data['users'][0]['createdAt'])->toBeNull();
    });

    it('kills null-safe operator mutation for chatUser pivot', function (): void {
        $user = User::factory()->create();
        $chat = Chat::factory()->create();
        // Users collection is empty or doesn't contain $user->id
        $chat->setRelation('users', new Collection);

        $action = new ChatListUser;
        $reflection = new ReflectionClass(ChatListUser::class);
        $method = $reflection->getMethod('buildChatUserItem');

        $result = $method->invoke($action, $chat, $user->id);

        expect($result['isMuted'])->toBeNull()
            ->and($result['ban'])->toBeFalse();
    });
});
