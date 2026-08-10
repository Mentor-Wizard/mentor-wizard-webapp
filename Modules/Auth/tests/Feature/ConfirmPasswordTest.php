<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Symfony\Component\HttpFoundation\Response;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
});

test('password confirmation passed', function (): void {
    $user = User::factory()->create();
    actingAs($user);

    $response = $this->postJson(route('password.confirm'), ['password' => 'password'])
        ->assertStatus(Response::HTTP_FOUND);

    $response->assertRedirect(route('pages.dashboard'));
});

test('user is not authorized', function (): void {
    $this->get(route('pages.password.confirm'))
        ->assertStatus(Response::HTTP_FOUND);
});

test('the provided password is incorrect', function (): void {
    $user = User::factory()->create();
    actingAs($user);

    $response = $this->postJson(route('password.confirm'), ['password' => '1111'])
        ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

    $this->assertFalse($response->isRedirection());
});
