<?php

declare(strict_types=1);

use App\Actions\Auth\Register\GetRegistrationPage;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

mutates(GetRegistrationPage::class);

describe('Password Reset Flow', function () {
    beforeEach(function () {
        $this->seed(RoleSeeder::class);
        Notification::fake();
    });

    it('renders forgot password page', function () {
        $this->get(route('password.request'))
            ->assertInertia(fn ($page) => $page
                ->component('Auth/ForgotPassword')
            );
    });

    it('sends password reset link', function () {
        $user = User::factory()->create();

        $response = $this->post(route('password.email'), [
            'email' => $user->email,
        ]);

        $response->assertSessionHas('status', trans(Password::RESET_LINK_SENT));

        Notification::assertSentTo($user, ResetPassword::class);
    });

    it('fails to send reset link for non-existent email', function () {
        $response = $this->post(route('password.email'), [
            'email' => 'nonexistent@example.com',
        ]);

        $response->assertSessionHasErrors('email');
    });

    it('renders reset password page', function () {
        $user = User::factory()->create();

        $token = Password::createToken($user);

        $response = $this->get(route('password.reset', [
            'token' => $token,
            'email' => $user->email,
        ]));

        $response->assertInertia(fn ($page) => $page
            ->component('Auth/ResetPassword')
            ->has('email')
            ->has('token')
        );
    });

    it('resets password successfully', function () {
        $user = User::factory()->create();

        $token = Password::createToken($user);

        $response = $this->post(route('password.store'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status', trans(Password::PASSWORD_RESET));

        $this->assertTrue(
            auth()->attempt([
                'email' => $user->email,
                'password' => 'new-password-123',
            ])
        );
    });

    it('fails to reset password with invalid token', function () {
        $user = User::factory()->create();

        $response = $this->post(route('password.store'), [
            'token' => 'invalid-token',
            'email' => $user->email,
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ]);

        $response->assertSessionHasErrors('email');
    });
});
