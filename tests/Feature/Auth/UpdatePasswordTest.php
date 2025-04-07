<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Symfony\Component\HttpFoundation\Response;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

describe('Successful scenarios', function () {
    it('updates the password successfully', function () {
        $user = User::factory()->create();

        actingAs($user);

        $this->put(route('password.update'), [
            'current_password' => 'password',
            'password' => 'new-secret-password',
            'password_confirmation' => 'new-secret-password',
        ])
            ->assertStatus(Response::HTTP_FOUND);

        $user->refresh();

        expect(Hash::check('new-secret-password', $user->password))->toBeTrue();
    });
});

describe('Unsuccessful scenarios', function () {
    it('does not update the password when current password is incorrect', function () {
        $user = User::factory()->create();

        actingAs($user);

        $this->put(route('password.update'), [
            'current_password' => 'asd',
            'password' => 'new-secret-password',
            'password_confirmation' => 'new-secret-password',
        ])
            ->assertStatus(Response::HTTP_FOUND);

        $user->refresh();

        expect(Hash::check('new-secret-password', $user->password))->toBeFalse();
    });

    it('does not update the password when confirmation password is incorrect', function () {
        $user = User::factory()->create();

        actingAs($user);

        $this->put(route('password.update'), [
            'current_password' => 'password',
            'password' => 'new-secret-password',
            'password_confirmation' => 'new-secret',
        ])
            ->assertStatus(Response::HTTP_FOUND);

        $user->refresh();

        expect(Hash::check('new-secret-password', $user->password))->toBeFalse();
    });
});
