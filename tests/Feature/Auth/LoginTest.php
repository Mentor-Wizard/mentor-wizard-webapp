<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Symfony\Component\HttpFoundation\Response;

describe('Get Login Page', function () {
    beforeEach(function () {
        $this->seed(RoleSeeder::class);
    });

    it('can see login page', function () {
        $this->get(route('login'))
            ->assertOk();
    });

    it('can login successful', function () {
        $user = User::factory()->create();

        $this->postJson(route('login.attempt'), [
            'email' => $user->email,
            'password' => 'password',
        ])
            ->assertStatus(Response::HTTP_FOUND)
            ->assertRedirect(route('pages.dashboard'));

        expect(Auth::check())->toBeTrue();
    });

    it('can not login with wrong email', function () {
        User::factory()->create();

        $this->postJson('login', [
            'email' => 'admin@admin.com',
            'password' => 'password',
        ])
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

        expect(Auth::check())->toBeFalse();
    });

    it('can not login with wrong password', function () {
        User::factory()->create();

        $this->postJson('login', [
            'email' => 'admin@admin.com',
            'password' => '1',
        ])
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

        expect(Auth::check())->toBeFalse();
    });
});
