<?php

declare(strict_types=1);

use Database\Seeders\RoleSeeder;
use Symfony\Component\HttpFoundation\Response;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
});

describe('Successful Scenarios', function (): void {
    it('user registration successful', function (): void {
        $newUserData = [
            'username'              => 'Test User',
            'email'                 => 'test@example.com',
            'password'              => 'password',
            'password_confirmation' => 'password',
            'timezone'              => 'Europe/Kyiv',
        ];

        $response = $this->postJson('register', $newUserData)
            ->assertStatus(Response::HTTP_FOUND);

        $this->assertTrue(Auth::check());
        $response->assertRedirect(route('pages.dashboard'));
    });
});
