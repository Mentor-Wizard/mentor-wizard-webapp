<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Modules\Chat\Actions\SendMessage;
use Modules\Chat\Enums\ChatStatusEnum;
use Modules\Chat\Events\ChatMessageEvent;
use Modules\Chat\Events\UnreadMessagesEvent;
use Modules\Chat\Http\Requests\ChatMessageRequest;
use Modules\Chat\Models\Chat;
use Modules\Chat\Models\ChatMessage;
use Symfony\Component\HttpFoundation\Response;

mutates(SendMessage::class);

describe('SendMessage', function (): void {
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

    it('uploads files and attaches them to the message', function (): void {
        Storage::fake('public');
        Event::fake();

        $companion = User::factory()->create();
        $companion->profile()->create(['name' => 'Companion Name']);

        $chat = Chat::factory()->create();
        $chat->users()->attach([
            $this->owner->id => [
                'status'   => ChatStatusEnum::ACTIVE->value,
                'is_muted' => false,
            ],
            $companion->id => [
                'status'   => ChatStatusEnum::ACTIVE->value,
                'is_muted' => true,
            ],
        ]);

        $file1 = UploadedFile::fake()->image('photo1.jpg');

        $payload = [
            'message' => 'Message with files',
            'files'   => [
                $file1,
            ],
        ];

        $response = $this->postJson(route('chat.send-message', $chat), $payload);

        $response->assertJsonPath('message.isRead', false);

        $response->assertJsonPath('message.timestamp', now()->format('Y-m-d\TH:i:s.u\Z'));
        Event::assertDispatched(fn (ChatMessageEvent $event): bool => isset($event->message->getAttributes()['is_read'])
            && $event->message->getAttributes()['is_read'] === false);

        $response->assertJsonCount(1, 'message.attachments');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message' => [
                    'id',
                    'sender',
                    'message',
                    'isRead',
                    'attachments',
                ],
            ]);

        $response->assertJson([
            'message' => [
                'message' => 'Message with files',
                'isRead'  => false,
            ],
        ]);

        $responseData = $response->json('message');
        expect($responseData['attachments'])->not->toBeEmpty()
            ->and($responseData['attachments'])->toHaveCount(1)
            ->and($responseData['attachments'][0]['name'])->toBe('photo1.jpg')
            ->and($responseData['attachments'][0])->toHaveKeys(['id', 'url', 'name']);

        $message = ChatMessage::query()->where('message', 'Message with files')->first();
        expect($message->getMedia('files'))->toHaveCount(1);

        Event::assertDispatched(fn (ChatMessageEvent $event): bool => $event->message->id === $message->id
            && $event->message->getMedia('files')->count() === 1);

        Event::assertDispatched(UnreadMessagesEvent::class);
    });

    it('throws authorization exception if companion chat status is not active', function (): void {
        $companion = User::factory()->create();
        $companion->profile()->create(['name' => 'Companion']);

        $chat = Chat::factory()->create();
        $chat->users()->attach([
            $this->owner->id => ['status' => ChatStatusEnum::ACTIVE->value],
            $companion->id   => ['status' => ChatStatusEnum::BANNED->value],
        ]);

        $response = $this->postJson(route('chat.send-message', $chat), [
            'message' => 'Hello',
        ]);

        $response->assertStatus(Response::HTTP_FORBIDDEN);
        $this->assertDatabaseEmpty('chat_messages');
    });

    it('ensures message is refreshed after adding media', function (): void {
        Storage::fake('public');

        $companion = User::factory()->create();
        $companion->profile()->create(['name' => 'Companion']);

        $chat = Chat::factory()->create();
        $chat->users()->attach([
            $this->owner->id => [
                'status'   => ChatStatusEnum::ACTIVE->value,
                'is_muted' => false,
            ],
            $companion->id => [
                'status'   => ChatStatusEnum::ACTIVE->value,
                'is_muted' => false,
            ],
        ]);

        $file1 = UploadedFile::fake()->image('photo1.jpg');

        $payload = [
            'message' => 'Message to refresh',
            'files'   => [$file1],
        ];

        $response = $this->postJson(route('chat.send-message', $chat), $payload);

        $response->assertStatus(200);

        $responseData = $response->json('message');
        expect($responseData['attachments'])->not->toBeEmpty();

        $message = ChatMessage::query()->orderByDesc('id')->first();
        expect($message->is_read)->toBeFalse();
    });

    it('skips file attachment if the item is not an instance of UploadedFile', function (): void {
        Storage::fake('public');
        $companion = User::factory()->create();
        $chat = Chat::factory()->create();
        $chat->users()->attach([
            $this->owner->id => ['status' => ChatStatusEnum::ACTIVE->value],
            $companion->id   => ['status' => ChatStatusEnum::ACTIVE->value],
        ]);

        $mockRequest = Mockery::mock(ChatMessageRequest::class);

        $mockRequest->shouldReceive('user')->andReturn($this->owner);
        $mockRequest->shouldReceive('validated')->andReturn([
            'message' => 'Manual call with garbage',
            'files'   => ['not-an-uploaded-file-instance'],
        ]);

        $action = new SendMessage;

        $response = $action->handle($chat, $mockRequest);

        expect($response->getStatusCode())->toBe(200);
        $message = ChatMessage::query()->where('message', 'Manual call with garbage')->first();
        expect($message->getMedia('files'))->toBeEmpty();
    });
});
