<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Password;
use Modules\Auth\Actions\Reset\ResetPassword;

mutates(ResetPassword::class);

describe('Password Reset Feature Test', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
    });

    it('can submit password reset request', function (): void {
        $user = User::factory()->create();

        $response = $this->post(route('password.email'), [
            'email' => $user->email,
        ]);

        $response->assertRedirect()
            ->assertSessionHas('status', trans(Password::RESET_LINK_SENT));
    });

    it('shows error for non-existent email', function (): void {
        $response = $this->post(route('password.email'), [
            'email' => 'nonexistent@example.com',
        ]);

        $response->assertSessionHasErrors('email');
    });

    it('throttles password reset requests', function (): void {
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
