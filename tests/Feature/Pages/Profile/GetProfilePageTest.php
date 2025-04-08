<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Inertia\Testing\AssertableInertia as Assert;

describe('Profile Page', function (): void {
    it('loads the profile page for an authenticated user', function (): void {
        $this->seed(RoleSeeder::class);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertInertia(fn (Assert $page): \Illuminate\Testing\Fluent\AssertableJson => $page
                ->component('Profile/Edit')
                ->has('mustVerifyEmail')
                ->where('status', null)
            );
    });

    it('does not allow an unauthenticated user to access the profile page', function (): void {
        $this->get(route('profile.edit'))
            ->assertRedirect(route('login'));
    });
});
