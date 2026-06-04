<?php

declare(strict_types=1);

use App\Enums\RoleEnum;
use App\Models\User;
use Spatie\Permission\Models\Role;

test('success create user test', function (): void {
    Role::create(['name' => RoleEnum::USER]);

    $user = User::factory()->create([
        'username' => 'Test User',
        'email'    => 'test@example.com',
    ]);

    expect($user->hasRole(RoleEnum::USER->value))->toBeTrue();
});

test('user slug is incremented if not unique', function (): void {
    Role::create([
        'name' => RoleEnum::USER,
    ]);

    User::factory()->create([
        'email'    => 'Email case',
        'username' => 'User case',
    ]);

    $secondUser = User::factory()->create([
        'email'    => 'email-case',
        'username' => 'user-case',
    ]);

    expect($secondUser->slug)->toBe('user-case-1');
});
