<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Auth\Notifications\ResetPassword as IlluminateResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Symfony\Component\HttpFoundation\Response;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

describe('Successful Scenarios', function () {
    it('renders the forgot password screen', function () {
        $this->get(route('password.request'))
            ->assertStatus(Response::HTTP_OK);
    });

    it('finds the user email', function () {
        $user = User::factory()->create();

        $this->postJson(route('password.email'), [
            'email' => $user->email,
        ])
            ->assertStatus(Response::HTTP_FOUND)
            ->assertRedirect();
    });

    it('renders the screen for creating a new password after email', function () {
        $this->get('/reset-password/6fcdb4b4d76b69f8a8b90a9aceb2a88e865e4df79c866ff76597e1ea803cfe68')
            ->assertStatus(Response::HTTP_OK);
    });

    it('allows a user to reset their password', function () {
        Notification::fake();

        $user = User::factory()->create();

        $this->postJson(route('password.email'), ['email' => $user->email])
            ->assertStatus(Response::HTTP_FOUND);

        Notification::assertSentTo($user, IlluminateResetPassword::class, function ($notification) use ($user) {
            $token = $notification->token;

            test()->post('/reset-password', [
                'token' => $token,
                'email' => $user->email,
                'password' => 'new-secure-password',
                'password_confirmation' => 'new-secure-password',
            ])->assertStatus(Response::HTTP_FOUND);

            $user->refresh();

            return Hash::check('new-secure-password', $user->password);
        });
    });
});

describe('Failure Scenarios', function () {
    it('returns 404 for wrong address to forgot password screen', function () {
        $this->get('/forgot-prd')
            ->assertStatus(Response::HTTP_NOT_FOUND);
    });

    it('returns 422 when user email is not found', function () {
        User::factory()->create();

        $this->postJson(route('password.email'), [
            'email' => 'adminoo@admino.com',
        ])
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    });

    it('returns 404 for wrong address to create new password screen', function () {
        $this->get('/forgot-prd/6fcdb4b4d76b69f8a8b90a9aceb2a88e865e4df79c866ff76597e1ea803cfe68')
            ->assertStatus(Response::HTTP_NOT_FOUND);
    });

    it('returns 405 when token is missing for create new password screen', function () {
        $this->get(route('password.store'))
            ->assertStatus(Response::HTTP_METHOD_NOT_ALLOWED);
    });

    it('fails when password_confirmation does not match', function () {
        Notification::fake();

        $user = User::factory()->create();
        $oldHashedPassword = $user->password;

        $this->postJson('/forgot-password', ['email' => $user->email])
            ->assertStatus(Response::HTTP_FOUND);

        Notification::assertSentTo($user, IlluminateResetPassword::class, function ($notification) use ($user, $oldHashedPassword) {
            $token = $notification->token;

            $this->post(route('password.email'), [
                'token' => $token,
                'email' => $user->email,
                'password' => 'new-secure-password',
                'password_confirmation' => 'invalid-confirmation',
            ])
                ->assertStatus(Response::HTTP_FOUND);

            $user->refresh();

            return $user->password === $oldHashedPassword;
        });
    });
});
