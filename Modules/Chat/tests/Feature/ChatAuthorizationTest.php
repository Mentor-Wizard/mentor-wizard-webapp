<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Event;
use Modules\Chat\Models\Chat;

describe('Chat route authorization (explicit Gate::policy registration)', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        Event::fake();

        $this->owner = User::factory()->create();
        $this->owner->profile()->create(['name' => 'Owner']);

        $this->companion = User::factory()->create();
        $this->companion->profile()->create(['name' => 'Companion']);

        $this->stranger = User::factory()->create();
        $this->stranger->profile()->create(['name' => 'Stranger']);

        $this->chat = Chat::factory()->create();
        $this->chat->users()->attach([
            $this->owner->id     => ['status' => 'active'],
            $this->companion->id => ['status' => 'active'],
        ]);
    });

    it('allows a chat participant to view chat messages', function (): void {
        $this->actingAs($this->owner);

        $response = $this->get(route('chat.messages', ['chat' => $this->chat->id]));

        $response->assertOk();
    });

    it('forbids a non-participant from viewing chat messages', function (): void {
        $this->actingAs($this->stranger);

        $response = $this->get(route('chat.messages', ['chat' => $this->chat->id]));

        $response->assertForbidden();
    });

    it('forbids a non-participant from sending a message to a chat they do not belong to', function (): void {
        $this->actingAs($this->stranger);

        $response = $this->post(route('chat.send-message', ['chat' => $this->chat->id]), [
            'message' => 'Hello',
        ]);

        $response->assertForbidden();
    });
});
