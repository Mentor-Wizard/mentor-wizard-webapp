<?php

declare(strict_types=1);

use App\Http\Resources\NotificationResource;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;

covers(NotificationResource::class);

describe('NotificationResource', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        $this->user = User::factory()->create();
    });

    it('exposes id, data, read_at, and created_at fields', function (): void {
        $id = Str::uuid()->toString();

        $notification = DatabaseNotification::query()->create([
            'id'              => $id,
            'type'            => 'App\Notifications\TestNotification',
            'notifiable_type' => User::class,
            'notifiable_id'   => $this->user->getKey(),
            'data'            => ['title' => 'Hello', 'message' => 'World'],
            'read_at'         => null,
        ]);

        $resource = NotificationResource::make($notification)->resolve();

        expect($resource)
            ->toHaveKey('id', $id)
            ->toHaveKey('data', ['title' => 'Hello', 'message' => 'World'])
            ->toHaveKey('read_at', null)
            ->toHaveKey('created_at');
    });

    it('formats read_at as ISO 8601 when set', function (): void {
        $readAt = now()->startOfMinute();

        $notification = DatabaseNotification::query()->create([
            'id'              => Str::uuid(),
            'type'            => 'App\Notifications\TestNotification',
            'notifiable_type' => User::class,
            'notifiable_id'   => $this->user->getKey(),
            'data'            => ['title' => 'Read'],
            'read_at'         => $readAt,
        ]);

        $resource = NotificationResource::make($notification)->resolve();

        expect($resource['read_at'])->toBe($readAt->toIso8601String());
    });

    it('formats created_at as ISO 8601', function (): void {
        $notification = DatabaseNotification::query()->create([
            'id'              => Str::uuid(),
            'type'            => 'App\Notifications\TestNotification',
            'notifiable_type' => User::class,
            'notifiable_id'   => $this->user->getKey(),
            'data'            => ['title' => 'Created'],
            'read_at'         => null,
        ]);

        $resource = NotificationResource::make($notification)->resolve();

        expect($resource['created_at'])->toBe($notification->created_at->toIso8601String());
    });

    it('does not expose internal fields like type or notifiable_id', function (): void {
        $notification = DatabaseNotification::query()->create([
            'id'              => Str::uuid(),
            'type'            => 'App\Notifications\TestNotification',
            'notifiable_type' => User::class,
            'notifiable_id'   => $this->user->getKey(),
            'data'            => ['title' => 'Scoped'],
            'read_at'         => null,
        ]);

        $resource = NotificationResource::make($notification)->resolve();

        expect($resource)
            ->not->toHaveKey('type')
            ->not->toHaveKey('notifiable_id')
            ->not->toHaveKey('notifiable_type')
            ->not->toHaveKey('updated_at');
    });
});
