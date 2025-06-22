<?php

declare(strict_types=1);

use App\Actions\Pages\WelcomePage;
use App\Enums\RoleEnum;
use App\Enums\RoleGuardEnum;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\Response;

covers(WelcomePage::class);

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
    $this->seed(UserSeeder::class);
});
it('returns a successful response', function (): void {
    $role = Role::findByName(RoleEnum::MENTOR->value, RoleGuardEnum::MENTOR->value);
    $user = User::factory()->create([
        'username'          => 'johndoe',
        'email'             => 'john@example.com',
        'email_verified_at' => now(),
    ]);
    $user->syncRoles($role);

    $response = $this->get(route('pages.welcome'));
    $response->assertStatus(Response::HTTP_OK);
});
