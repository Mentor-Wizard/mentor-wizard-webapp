<?php

declare(strict_types=1);

use App\Enums\RoleEnum;
use App\Enums\RoleGuardEnum;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('success create user test', function (): void {
    Role::create(['name' => RoleEnum::USER, 'guard_name' => RoleGuardEnum::USER]);

    $user = User::factory()->create([
        'username' => 'Test User',
        'email'    => 'test@example.com',
    ]);

    expect($user->hasRole(RoleEnum::USER->value))->toBeTrue();
});

test('user slug is incremented if not unique', function (): void {
    // Створюємо роль, щоб avoid exception у assignRole
    Role::create([
        'name'       => RoleEnum::USER,
        'guard_name' => RoleGuardEnum::USER,
    ]);

    User::factory()->create([
        'email'    => 'Email case',
        'username' => 'User 1',
    ]);

    $secondUser = User::factory()->create([
        'email'    => 'email-case',
        'username' => 'User 2',
    ]);

    expect($secondUser->slug)->toBe('email-case-1');
});
