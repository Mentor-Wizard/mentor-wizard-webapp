<?php

declare(strict_types=1);

use App\Actions\Auth\Reset\ResetPassword;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Password;

mutates(ResetPassword::class);

describe('Password Reset Feature Test', function () {
    beforeEach(function () {
        $this->seed(RoleSeeder::class);
    });

    it('can submit password reset request', function () {
        $user = User::factory()->create();

        $response = $this->post(route('password.email'), [
            'email' => $user->email,
        ]);

        $response->assertRedirect()
            ->assertSessionHas('status', trans(Password::RESET_LINK_SENT));
    });

    it('shows error for non-existent email', function () {
        $response = $this->post(route('password.email'), [
            'email' => 'nonexistent@example.com',
        ]);

        $response->assertSessionHasErrors('email');
    });

    it('throttles password reset requests', function () {
        $user = User::factory()->create();

        // Simulate multiple password reset requests
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('password.email'), [
                'email' => $user->email,
            ]);
        }

        $response = $this->post(route('password.email'), [
            'email' => $user->email,
        ]);

        $response->assertSessionHasErrors('email');
    });
});
