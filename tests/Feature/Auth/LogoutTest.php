<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\post;

describe('Logout', function () {
    beforeEach(function () {
        $this->seed(RoleSeeder::class);
    });

    it('allows a user to logout successfully', function () {
        $user = User::factory()->create();

        actingAs($user);

        expect(Auth::check())->toBeTrue();

        post(route('logout'))
            ->assertStatus(Response::HTTP_FOUND)
            ->assertRedirect(route('login'));

        $this->assertGuest();
    });

    it('returns redirect even if user is not authenticated', function () {
        post(route('logout'))
            ->assertStatus(Response::HTTP_FOUND)
            ->assertRedirect(route('login'));
    });
});
