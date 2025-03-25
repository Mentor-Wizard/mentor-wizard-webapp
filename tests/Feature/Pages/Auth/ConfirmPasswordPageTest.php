<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Inertia\Testing\AssertableInertia as Assert;

describe('Confirm Password Page Feature Test', function () {
    it('can access confirm password page when authenticated', function () {
        $this->seed(RoleSeeder::class);

        $user = User::factory()->create();

        $this->actingAs($user);

        $response = $this->get(route('password.confirm'));

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Auth/ConfirmPassword')
            );
    });

    it('redirects unauthenticated users to login', function () {
        $response = $this->get(route('password.confirm'));

        $response->assertRedirect(route('login'));
    });
});
