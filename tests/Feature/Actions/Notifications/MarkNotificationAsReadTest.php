<?php

declare(strict_types=1);

use App\Actions\Notifications\MarkNotificationAsRead;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

covers(MarkNotificationAsRead::class);

describe('MarkNotificationAsRead', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        $this->user = User::factory()->create();
    });

    it('requires authentication', function (): void {
        postJson(route('notifications.read', ['id' => Str::uuid()]))
            ->assertUnauthorized();
    });

    it('marks the notification as read and returns 204', function (): void {
        $id = Str::uuid()->toString();

        DatabaseNotification::query()->insert([
            'id'              => $id,
            'type'            => 'App\Notifications\TestNotification',
            'notifiable_type' => $this->user->getMorphClass(),
            'notifiable_id'   => $this->user->getKey(),
            'data'            => json_encode(['title' => 'Test']),
            'read_at'         => null,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        actingAs($this->user)
            ->postJson(route('notifications.read', ['id' => $id]))
            ->assertNoContent();

        expect(DatabaseNotification::query()->find($id)->read_at)->not->toBeNull();
    });

    it('does not affect other notifications belonging to the same user', function (): void {
        $targetId = Str::uuid()->toString();
        $otherId = Str::uuid()->toString();

        DatabaseNotification::query()->insert([
            [
                'id'              => $targetId,
                'type'            => 'App\Notifications\TestNotification',
                'notifiable_type' => $this->user->getMorphClass(),
                'notifiable_id'   => $this->user->getKey(),
                'data'            => json_encode(['title' => 'Target']),
                'read_at'         => null,
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'id'              => $otherId,
                'type'            => 'App\Notifications\TestNotification',
                'notifiable_type' => $this->user->getMorphClass(),
                'notifiable_id'   => $this->user->getKey(),
                'data'            => json_encode(['title' => 'Other']),
                'read_at'         => null,
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
        ]);

        actingAs($this->user)
            ->postJson(route('notifications.read', ['id' => $targetId]))
            ->assertNoContent();

        expect(DatabaseNotification::query()->find($targetId)->read_at)->not->toBeNull()
            ->and(DatabaseNotification::query()->find($otherId)->read_at)->toBeNull();
    });

    it('does not mark notifications belonging to another user', function (): void {
        $otherUser = User::factory()->create();
        $id = Str::uuid()->toString();

        DatabaseNotification::query()->insert([
            'id'              => $id,
            'type'            => 'App\Notifications\TestNotification',
            'notifiable_type' => $otherUser->getMorphClass(),
            'notifiable_id'   => $otherUser->getKey(),
            'data'            => json_encode(['title' => 'Private']),
            'read_at'         => null,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        actingAs($this->user)
            ->postJson(route('notifications.read', ['id' => $id]))
            ->assertNoContent();

        expect(DatabaseNotification::query()->find($id)->read_at)->toBeNull();
    });
});
