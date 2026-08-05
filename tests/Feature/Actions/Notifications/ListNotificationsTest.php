<?php

declare(strict_types=1);

use App\Actions\Notifications\ListNotifications;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;

covers(ListNotifications::class);

describe('ListNotifications', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        $this->user = User::factory()->create();
    });

    it('requires authentication', function (): void {
        getJson(route('notifications.index'))
            ->assertUnauthorized();
    });

    it('returns paginated notifications for the authenticated user', function (): void {
        DatabaseNotification::query()->insert([
            'id'              => Str::uuid(),
            'type'            => 'App\Notifications\TestNotification',
            'notifiable_type' => (new User)->getMorphClass(),
            'notifiable_id'   => $this->user->getKey(),
            'data'            => json_encode(['title' => 'Hello', 'message' => 'World']),
            'read_at'         => null,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        actingAs($this->user)
            ->getJson(route('notifications.index'))
            ->assertOk()
            ->assertJsonStructure([
                'data' => [['id', 'data', 'read_at', 'created_at']],
                'links',
                'meta',
            ])
            ->assertJsonCount(1, 'data');
    });

    it('does not expose notifications belonging to another user', function (): void {
        $otherUser = User::factory()->create();

        DatabaseNotification::query()->insert([
            'id'              => Str::uuid(),
            'type'            => 'App\Notifications\TestNotification',
            'notifiable_type' => (new User)->getMorphClass(),
            'notifiable_id'   => $otherUser->getKey(),
            'data'            => json_encode(['title' => 'Private']),
            'read_at'         => null,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        actingAs($this->user)
            ->getJson(route('notifications.index'))
            ->assertOk()
            ->assertJsonCount(0, 'data');
    });

    it('returns notifications ordered latest first', function (): void {
        $ids = [];

        foreach (range(1, 3) as $i) {
            $id = Str::uuid()->toString();
            $ids[] = $id;

            DatabaseNotification::query()->insert([
                'id'              => $id,
                'type'            => 'App\Notifications\TestNotification',
                'notifiable_type' => (new User)->getMorphClass(),
                'notifiable_id'   => $this->user->getKey(),
                'data'            => json_encode(['title' => 'Notification '.$i]),
                'read_at'         => null,
                'created_at'      => now()->addSeconds($i),
                'updated_at'      => now()->addSeconds($i),
            ]);
        }

        $response = actingAs($this->user)
            ->getJson(route('notifications.index'))
            ->assertOk();

        expect($response->json('data.0.id'))->toBe($ids[2])
            ->and($response->json('data.2.id'))->toBe($ids[0]);
    });

    it('paginates using User::NOTIFICATIONS_PER_PAGE', function (): void {
        $total = User::NOTIFICATIONS_PER_PAGE + 5;

        $rows = array_map(fn (): array => [
            'id'              => Str::uuid(),
            'type'            => 'App\Notifications\TestNotification',
            'notifiable_type' => (new User)->getMorphClass(),
            'notifiable_id'   => $this->user->getKey(),
            'data'            => json_encode(['title' => 'N']),
            'read_at'         => null,
            'created_at'      => now(),
            'updated_at'      => now(),
        ], range(1, $total));

        DatabaseNotification::query()->insert($rows);

        $response = actingAs($this->user)
            ->getJson(route('notifications.index'))
            ->assertOk();

        expect($response->json('data'))->toHaveCount(User::NOTIFICATIONS_PER_PAGE)
            ->and($response->json('meta.total'))->toBe($total)
            ->and($response->json('meta.per_page'))->toBe(User::NOTIFICATIONS_PER_PAGE);
    });

    it('returns correct resource fields', function (): void {
        $id = Str::uuid()->toString();

        DatabaseNotification::query()->insert([
            'id'              => $id,
            'type'            => 'App\Notifications\TestNotification',
            'notifiable_type' => (new User)->getMorphClass(),
            'notifiable_id'   => $this->user->getKey(),
            'data'            => json_encode(['title' => 'Hi', 'message' => 'There']),
            'read_at'         => null,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        actingAs($this->user)
            ->getJson(route('notifications.index'))
            ->assertOk()
            ->assertJsonPath('data.0.id', $id)
            ->assertJsonPath('data.0.data.title', 'Hi')
            ->assertJsonPath('data.0.data.message', 'There')
            ->assertJsonPath('data.0.read_at', null);
    });
});
