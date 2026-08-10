<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Chat\Models\Chat;
use Modules\Chat\Models\ChatMessage;

describe('DownloadChatFile Feature', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        Storage::fake('public');

        $this->user = User::factory()->create();
        $this->user->profile()->create(['name' => 'User']);

        $this->companion = User::factory()->create();
        $this->companion->profile()->create(['name' => 'Companion']);

        $this->stranger = User::factory()->create();
        $this->stranger->profile()->create(['name' => 'Stranger']);

        $this->chat = Chat::factory()->create();
        $this->chat->users()->attach([
            $this->user->id      => ['status' => 'active'],
            $this->companion->id => ['status' => 'active'],
        ]);

        $this->message = ChatMessage::factory()->create([
            'chat_id' => $this->chat->id,
            'user_id' => $this->user->id,
            'message' => 'Hello',
        ]);

        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');
        $this->media = $this->message->addMedia($file)->toMediaCollection('files');
    });

    it('allows a chat participant to download the file', function (): void {
        $this->actingAs($this->user);

        $response = $this->get(route('chat.message.download', [
            'message' => $this->message->id,
            'media'   => $this->media->id,
        ]));

        $response->assertOk();
        $response->assertDownload('document.pdf');
    });

    it('allows the companion to download the file', function (): void {
        $this->actingAs($this->companion);

        $response = $this->get(route('chat.message.download', [
            'message' => $this->message->id,
            'media'   => $this->media->id,
        ]));

        $response->assertOk();
        $response->assertDownload('document.pdf');
    });

    it('forbids a non-participant from downloading the file', function (): void {
        $this->actingAs($this->stranger);

        $response = $this->get(route('chat.message.download', [
            'message' => $this->message->id,
            'media'   => $this->media->id,
        ]));

        $response->assertForbidden();
    });

    it('returns 404 if media does not belong to the message', function (): void {
        $this->actingAs($this->user);

        $otherMessage = ChatMessage::factory()->create([
            'chat_id' => $this->chat->id,
            'user_id' => $this->user->id,
            'message' => 'Other message',
        ]);

        $response = $this->get(route('chat.message.download', [
            'message' => $otherMessage->id,
            'media'   => $this->media->id,
        ]));

        $response->assertNotFound();
    });
});
