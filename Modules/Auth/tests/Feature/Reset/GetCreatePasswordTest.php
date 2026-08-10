<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Modules\Auth\Actions\Register\GetRegistrationPage;

mutates(GetRegistrationPage::class);

describe('Password Reset Flow', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        Notification::fake();
    });

    it('renders forgot password page', function (): void {
        $this->get(route('password.request'))
            ->assertInertia(fn ($page) => $page
                ->component('Auth/ForgotPassword')
            );
    });

    it('sends password reset link', function (): void {
        $user = User::factory()->create();

        $response = $this->post(route('password.email'), [
            'email' => $user->email,
        ]);

        $response->assertSessionHas('status', trans(Password::RESET_LINK_SENT));

        Notification::assertSentTo($user, ResetPassword::class);
    });

    it('fails to send reset link for non-existent email', function (): void {
        $response = $this->post(route('password.email'), [
            'email' => 'nonexistent@example.com',
        ]);

        $response->assertSessionHasErrors('email');

        Notification::assertNothingSent();
    });

    it('renders reset password page', function (): void {
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

    it('resets password successfully', function (): void {
        $user = User::factory()->create();

        $token = Password::createToken($user);

        $response = $this->post(route('password.store'), [
            'token'                 => $token,
            'email'                 => $user->email,
            'password'              => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status', trans(Password::PASSWORD_RESET));

        $this->assertTrue(
            auth()->attempt([
                'email'    => $user->email,
                'password' => 'new-password-123',
            ])
        );
    });

    it('fails to reset password with invalid token', function (): void {
        $user = User::factory()->create();

        $response = $this->post(route('password.store'), [
            'token'                 => 'invalid-token',
            'email'                 => $user->email,
            'password'              => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ]);

        $response->assertSessionHasErrors('email');
    });
});
