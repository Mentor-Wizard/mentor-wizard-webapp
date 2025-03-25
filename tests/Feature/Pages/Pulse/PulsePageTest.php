<?php

declare(strict_types=1);

use App\Enums\RoleEnum;
use App\Enums\RoleGuardEnum;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

test('denies access to Pulse for unauthorized users', function () {
    Role::create(['name' => RoleEnum::USER, 'guard_name' => RoleGuardEnum::USER]);

    $user = User::factory()->create([
        'username' => 'Test User',
        'email' => 'test@example.com',
    ]);

    actingAs($user)
        ->get('/pulse')
        ->assertForbidden();
});

test('allows access to Pulse for admin users', function () {
    Role::create(['name' => RoleEnum::USER->value, 'guard_name' => RoleGuardEnum::USER->value]);
    $role = Role::create(['name' => RoleEnum::ADMIN->value, 'guard_name' => RoleGuardEnum::ADMIN->value]);

    $admin = User::factory()->create([
        'username' => 'Test ADMIN',
        'email' => 'admi@example.com',
    ]);

    $admin->assignRole($role);

    actingAs($admin)
        ->get('/pulse')
        ->assertOk();
});

test('allows access to Pulse for superadmin users', function () {
    Role::create(['name' => RoleEnum::USER->value, 'guard_name' => RoleGuardEnum::USER->value]);
    $role = Role::create(['name' => RoleEnum::ADMIN->value, 'guard_name' => RoleGuardEnum::ADMIN->value]);

    $superadmin = User::factory()->create([
        'username' => 'Test SUPERADMIN',
        'email' => 'superadmin@example.com',
    ]);

    $superadmin->assignRole($role);

    actingAs($superadmin)
        ->get('/pulse')
        ->assertOk();
});
