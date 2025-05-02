<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\RoleSeeder;

it('Visited an unregistered page', function (): void {
    $response = $this->get('/page-not-found');

    $response->assertStatus(404)
        ->assertSee(404)
        ->assertSee('Not Found');
});

it('Visited site in maintenance mode', function (): void {
    Artisan::call('down');

    $response = $this->get('/');

    $response->assertStatus(503)
        ->assertSee('Service Unavailable');

    Artisan::call('up');
});

it('Spam requests to url', function (): void {
    $this->seed(RoleSeeder::class);
    $user = User::factory([
        'email_verified_at' => null,
    ])->create();

    foreach (range(1, 7) as $i) {
        unset($i);
        $response = $this->actingAs($user)->post('email/verification-notification');
    }

    $response->assertStatus(429)
        ->assertSee('Too Many Requests');
});
