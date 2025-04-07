<?php

declare(strict_types=1);

use App\Enums\RoleEnum;
use App\Enums\RoleGuardEnum;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('success create user test', function () {
    Role::create(['name' => RoleEnum::USER, 'guard_name' => RoleGuardEnum::USER]);

    $user = User::factory()->create([
        'username' => 'Test User',
        'email' => 'test@example.com',
    ]);

    expect($user->hasRole(RoleEnum::USER->value))->toBeTrue();
});
