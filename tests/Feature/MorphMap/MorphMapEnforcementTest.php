<?php

declare(strict_types=1);

use App\Enums\RoleEnum;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\ClassMorphViolationException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Chat\Models\Chat;
use Modules\Chat\Models\ChatMessage;
use Modules\UserProfile\Models\UserProfile;
use Spatie\Permission\Models\Role;

describe('enforceMorphMap fail-loud guarantee', function (): void {
    it('throws for a model that is not registered in the morph map', function (): void {
        $unmapped = new class extends Model
        {
            use HasFactory;
        };

        expect(fn () => $unmapped->getMorphClass())
            ->toThrow(ClassMorphViolationException::class);
    });

    it('resolves getMorphedModel(null) for an alias not present in the map instead of a mapped model', function (): void {
        expect(Relation::getMorphedModel('not-a-registered-alias'))->toBeNull();
    });

    it('keeps every currently-participating polymorphic model registered', function (): void {
        expect(Relation::getMorphedModel('user'))->toBe(User::class)
            ->and(Relation::getMorphedModel('user_profile'))->toBe(UserProfile::class)
            ->and(Relation::getMorphedModel('chat'))->toBe(Chat::class)
            ->and(Relation::getMorphedModel('chat_message'))->toBe(ChatMessage::class);
    });
});

describe('morph map backfill migration idempotency', function (): void {
    it('is a no-op when run a second time after rows are already aliased', function (): void {
        $this->seed(RoleSeeder::class);

        $user = User::factory()->create();
        $role = Role::query()->where('name', RoleEnum::MENTI->value)->firstOrFail();

        DB::table('model_has_roles')->insert([
            'role_id'    => $role->getKey(),
            'model_type' => User::class,
            'model_id'   => $user->getKey(),
        ]);

        $migration = require base_path('database/migrations/2026_08_04_120000_backfill_morph_map_aliases.php');

        $migration->up();

        expect(DB::table('model_has_roles')->where('model_id', $user->getKey())->value('model_type'))->toBe('user');

        $migration->up();

        expect(DB::table('model_has_roles')->where('model_id', $user->getKey())->value('model_type'))->toBe('user');
    });

    it('is reversible without error and restores the legacy FQCN', function (): void {
        $this->seed(RoleSeeder::class);

        $user = User::factory()->create();

        DB::table('notifications')->insert([
            'id'              => (string) Str::uuid(),
            'type'            => 'App\\Notifications\\Legacy',
            'notifiable_type' => 'user',
            'notifiable_id'   => $user->getKey(),
            'data'            => '{}',
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        $migration = require base_path('database/migrations/2026_08_04_120000_backfill_morph_map_aliases.php');

        $migration->down();

        expect(DB::table('notifications')->where('notifiable_id', $user->getKey())->value('notifiable_type'))->toBe(User::class);

        $migration->down();

        expect(DB::table('notifications')->where('notifiable_id', $user->getKey())->value('notifiable_type'))->toBe(User::class);
    });
});
