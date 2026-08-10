<?php

declare(strict_types=1);

use App\Actions\Notifications\MarkAllNotificationsAsRead;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

covers(MarkAllNotificationsAsRead::class);

describe('MarkAllNotificationsAsRead', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        $this->user = User::factory()->create();
    });

    it('requires authentication', function (): void {
        postJson(route('notifications.read-all'))
            ->assertUnauthorized();
    });

    it('marks all unread notifications as read and returns 204', function (): void {
        DatabaseNotification::query()->insert([
            [
                'id'              => Str::uuid(),
                'type'            => 'App\Notifications\TestNotification',
                'notifiable_type' => (new User)->getMorphClass(),
                'notifiable_id'   => $this->user->getKey(),
                'data'            => json_encode(['title' => 'First']),
                'read_at'         => null,
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'id'              => Str::uuid(),
                'type'            => 'App\Notifications\TestNotification',
                'notifiable_type' => (new User)->getMorphClass(),
                'notifiable_id'   => $this->user->getKey(),
                'data'            => json_encode(['title' => 'Second']),
                'read_at'         => null,
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
        ]);

        actingAs($this->user)
            ->postJson(route('notifications.read-all'))
            ->assertNoContent();

        expect(
            $this->user->unreadNotifications()->count()
        )->toBe(0);
    });

    it('does not affect already-read notifications', function (): void {
        $alreadyReadAt = now()->subHour();

        DatabaseNotification::query()->insert([
            'id'              => Str::uuid(),
            'type'            => 'App\Notifications\TestNotification',
            'notifiable_type' => (new User)->getMorphClass(),
            'notifiable_id'   => $this->user->getKey(),
            'data'            => json_encode(['title' => 'Already read']),
            'read_at'         => $alreadyReadAt,
            'created_at'      => now()->subHours(2),
            'updated_at'      => now()->subHours(2),
        ]);

        actingAs($this->user)
            ->postJson(route('notifications.read-all'))
            ->assertNoContent();

        $notification = $this->user->notifications()->first();

        expect($notification->read_at->toDateTimeString())
            ->toBe($alreadyReadAt->toDateTimeString());
    });

    it('does not affect notifications belonging to another user', function (): void {
        $otherUser = User::factory()->create();
        $id = Str::uuid()->toString();

        DatabaseNotification::query()->insert([
            'id'              => $id,
            'type'            => 'App\Notifications\TestNotification',
            'notifiable_type' => (new User)->getMorphClass(),
            'notifiable_id'   => $otherUser->getKey(),
            'data'            => json_encode(['title' => 'Other user']),
            'read_at'         => null,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        actingAs($this->user)
            ->postJson(route('notifications.read-all'))
            ->assertNoContent();

        expect(DatabaseNotification::query()->find($id)->read_at)->toBeNull();
    });
});
