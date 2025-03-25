<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Symfony\Component\HttpFoundation\Response;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\seed;

beforeEach(function () {
    seed(RoleSeeder::class);
    $user = User::factory()->create();
    actingAs($user);
});

describe('Successful Scenarios', function () {
    it('renders the profile page', function () {
        $this->get(route('profile.edit'))
            ->assertStatus(Response::HTTP_OK);
    });

    it('updates the name and email successfully', function () {
        $user = User::factory()->create();

        $this->actingAs($user)->patch(route('profile.update'), [
            'username' => 'change_name',
            'email' => 'change_email@email.com',
        ])
            ->assertStatus(Response::HTTP_FOUND)
            ->assertRedirect(route('profile.edit'));

        $user->refresh();

        expect($user->username)->toBe('change_name')
            ->and($user->email)->toBe('change_email@email.com');
    });
});

describe('Unsuccessful Scenarios', function () {
    it('returns 404 for incorrect profile page address', function () {
        $this->get('/profil-ee')->assertStatus(Response::HTTP_NOT_FOUND);
    });

    it('does not allow name longer than the limit', function () {
        $user = User::factory()->create();

        $name = str_repeat('test', 300);
        $response = $this->patch(route('profile.update'), [
            'username' => $name,
            'email' => 'change_email@email.com',
        ])
            ->assertStatus(Response::HTTP_FOUND);

        $this->assertFalse($response->isRedirect(route('profile.edit')));

        $user->refresh();
        $this->assertNotEquals($name, $user->username);
    });

    it('does not allow name shorter than the limit', function () {
        $user = User::factory()->create();

        $response = $this->patch(route('profile.update'), [
            'username' => 'A',
            'email' => 'change_email@email.com',
        ])
            ->assertStatus(Response::HTTP_FOUND);

        $this->assertFalse($response->isRedirect(route('profile.edit')));

        $user->refresh();
        $this->assertNotEquals('A', $user->username);
    });

    it('does not allow empty name', function () {
        $user = User::factory()->create();

        $response = $this->patch(route('profile.update'), [
            'username' => '',
            'email' => 'change_email@email.com',
        ])
            ->assertStatus(Response::HTTP_FOUND);

        $this->assertFalse($response->isRedirect(route('profile.edit')));

        $user->refresh();
        $this->assertNotEquals('', $user->username);
    });

    it('does not allow non-unique email', function () {
        $user = User::factory()->create();
        $secondUser = User::factory()->create();

        $response = $this->patch(route('profile.update'), [
            'name' => 'change_name',
            'email' => $secondUser->email,
        ])
            ->assertStatus(Response::HTTP_FOUND);

        $this->assertFalse($response->isRedirect(route('profile.edit')));

        $user->refresh();
        $this->assertNotEquals($secondUser->email, $user->email);
    });

    it('does not allow email longer than the limit', function () {
        $user = User::factory()->create();

        $email = str_repeat('test', 300).'@admin.com';
        $response = $this->patch(route('profile.update'), [
            'name' => 'change_name',
            'email' => $email,
        ])
            ->assertStatus(Response::HTTP_FOUND);

        $this->assertFalse($response->isRedirect(route('profile.edit')));

        $user->refresh();
        $this->assertNotEquals($email, $user->email);
    });

    it('does not allow empty email', function () {
        $user = User::factory()->create();

        $response = $this->patch(route('profile.update'), [
            'name' => 'change_name',
            'email' => '',
        ])
            ->assertStatus(Response::HTTP_FOUND);

        $this->assertFalse($response->isRedirect(route('profile.edit')));

        $user->refresh();
        $this->assertNotEquals('', $user->email);
    });
});
