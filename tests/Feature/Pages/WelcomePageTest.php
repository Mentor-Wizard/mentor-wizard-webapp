<?php

declare(strict_types=1);

use App\Actions\Pages\WelcomePage;
use App\Enums\RoleEnum;
use App\Models\User;
use Database\Seeders\UserSeeder;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\Response;

covers(WelcomePage::class);

beforeEach(function (): void {
    // Create roles only when needed, avoid expensive seeding
    Role::query()->firstOrCreate(['name' => RoleEnum::MENTOR->value]);
    Role::query()->firstOrCreate(['name' => RoleEnum::USER->value]);
    // Remove UserSeeder completely - it downloads images from external URLs
});
it('returns a successful response', function (): void {
    $role = Role::findByName(RoleEnum::MENTOR->value);
    $user = User::factory()->create([
        'username'          => 'johndoe',
        'email'             => 'john@example.com',
        'email_verified_at' => now(),
    ]);
    $user->syncRoles($role);

    $response = $this->get(route('pages.welcome'));
    $response->assertStatus(Response::HTTP_OK);
});
