<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Symfony\Component\HttpFoundation\Response;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
});

describe('Successful Scenarios', function (): void {
    it('registration screen can be rendered', function (): void {
        $response = $this->get(route('register'));

        $response->assertStatus(Response::HTTP_OK);
    });

    it('user registration successful', function (): void {
        $newUserData = [
            'username'              => 'Test User',
            'email'                 => 'test@example.com',
            'password'              => 'password',
            'password_confirmation' => 'password',
        ];

        $response = $this->postJson('register', $newUserData)
            ->assertStatus(Response::HTTP_FOUND);

        $this->assertTrue(Auth::check());
        $response->assertRedirect(route('pages.dashboard'));
    });
});

describe('Unsuccessful Scenarios', function (): void {
    it('wrong registration address', function (): void {
        $response = $this->get('/regis-ter');

        $response->assertStatus(Response::HTTP_NOT_FOUND);
    });

    it('user registration password is too small', function (): void {
        $newUserData = [
            'name'                  => 'Test User',
            'email'                 => 'test@example.com',
            'password'              => 'p',
            'password_confirmation' => 'p',
        ];

        $response = $this->postJson('register', $newUserData)
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

        $this->assertFalse(Auth::check());
        $this->assertFalse($response->isRedirect());
    });

    it('user registration confirm password is empty', function (): void {
        $newUserData = [
            'name'                  => 'Test User',
            'email'                 => 'test@example.com',
            'password'              => 'password',
            'password_confirmation' => '',
        ];

        $response = $this->postJson('register', $newUserData)
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

        $this->assertFalse(Auth::check());
        $this->assertFalse($response->isRedirect());
    });

    it('user registration email not unique', function (): void {
        $user = User::factory()->create();

        $newUserData = [
            'name'                  => 'Test User',
            'email'                 => $user->email,
            'password'              => 'password',
            'password_confirmation' => '',
        ];

        $response = $this->postJson('register', $newUserData)
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

        $this->assertFalse(Auth::check());
        $this->assertFalse($response->isRedirect());
    });

    it('user registration name is too small', function (): void {
        $newUserData = [
            'name'                  => 'T',
            'email'                 => 'test@example.com',
            'password'              => 'password',
            'password_confirmation' => '',
        ];

        $response = $this->postJson('register', $newUserData)
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

        $this->assertFalse(Auth::check());
        $this->assertFalse($response->isRedirect());
    });
});
