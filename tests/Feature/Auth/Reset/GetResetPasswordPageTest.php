<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

describe('Password Reset Feature Test', function () {
    beforeEach(function () {
        $this->seed(RoleSeeder::class);
        Notification::fake();
    });

    it('sends a password reset email', function () {
        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        $this->post(route('password.email'), [
            'email' => $user->email,
        ])->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class);
    });

    it('does not send an email for a non-existent email', function () {
        $this->post(route('password.email'), [
            'email' => 'nonexistent@example.com',
        ])->assertSessionHasErrors('email');

        Notification::assertNothingSent();
    });

    it('resets the password successfully', function () {
        $user = User::factory()->create([
            'email' => 'reset@example.com',
            'password' => bcrypt('oldpassword'),
        ]);
        $token = Password::broker()->createToken($user);

        $this->post(route('password.store'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ])->assertRedirect(route('login'));

        expect(Auth::attempt([
            'email' => $user->email,
            'password' => 'newpassword123',
        ]))->toBeTrue();
    });
});
