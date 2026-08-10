<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\UserProfile\Models\UserProfile;
use Spatie\Permission\PermissionRegistrar;

/**
 * Data-only migration: backfills `*_type` polymorphic columns from the raw FQCN
 * strings that were written before `Relation::enforceMorphMap()` was introduced
 * (see App\Providers\AppServiceProvider::configMorphMap()) to the short, enforced
 * aliases. No schema/columns change — only values.
 *
 * Matches old class strings exactly (not a destructive rewrite): any row that is
 * not one of the mapped legacy FQCNs is left untouched.
 *
 * `Chat`/`ChatMessage` are intentionally referenced by their historical legacy
 * FQCN string literals, not `::class` constants: this migration backfills rows
 * written when those models still lived at `App\Models\Chat`/`App\Models\ChatMessage`
 * (before the DDD-migration Chat pilot moved them to `Modules\Chat\Models\*`). The
 * literal must stay pinned to the value that was actually persisted at the time,
 * independent of wherever the class lives today.
 */
return new class extends Migration
{
    /**
     * @var array<string, string>
     */
    private const array ALIAS_BY_CLASS = [
        User::class              => 'user',
        UserProfile::class       => 'user_profile',
        'App\Models\Chat'        => 'chat',
        'App\Models\ChatMessage' => 'chat_message',
    ];

    public function up(): void
    {
        DB::transaction(function (): void {
            foreach (self::ALIAS_BY_CLASS as $class => $alias) {
                DB::table('model_has_roles')->where('model_type', $class)->update(['model_type' => $alias]);
                DB::table('model_has_permissions')->where('model_type', $class)->update(['model_type' => $alias]);
                DB::table('media')->where('model_type', $class)->update(['model_type' => $alias]);
                DB::table('notifications')->where('notifiable_type', $class)->update(['notifiable_type' => $alias]);
            }
        });

        // spatie/laravel-permission caches role/permission assignments independently of the
        // DB values (spatie.permission.cache.*). Without this, roles assigned before this
        // migration would resolve against a stale, pre-backfill cache until its TTL expires.
        //
        // `resolve()` is used instead of constructor/method injection because
        // `Illuminate\Database\Migrations\Migrator::runMethod()` calls `$migration->up()`
        // directly — anonymous migration classes are never built through the container, so DI
        // is not physically available here. This is a deliberate, migration-only exception to
        // the project's "no resolve()/app() as service locator" rule, not a violation of it.
        resolve(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            foreach (self::ALIAS_BY_CLASS as $class => $alias) {
                DB::table('model_has_roles')->where('model_type', $alias)->update(['model_type' => $class]);
                DB::table('model_has_permissions')->where('model_type', $alias)->update(['model_type' => $class]);
                DB::table('media')->where('model_type', $alias)->update(['model_type' => $class]);
                DB::table('notifications')->where('notifiable_type', $alias)->update(['notifiable_type' => $class]);
            }
        });

        // Same DI-unavailability exception as in up() — see the comment there.
        resolve(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
