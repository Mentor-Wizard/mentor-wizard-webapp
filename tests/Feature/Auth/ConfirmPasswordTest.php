<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Symfony\Component\HttpFoundation\Response;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('confirm password screen can be rendered', function () {
    $user = User::factory()->create();
    actingAs($user);

    $this->get(route('password.confirm'))
        ->assertStatus(Response::HTTP_OK);
});

test('password confirmation passed', function () {
    $user = User::factory()->create();
    actingAs($user);

    $response = $this->postJson(route('password.confirm'), ['password' => 'password'])
        ->assertStatus(Response::HTTP_FOUND);

    $response->assertRedirect(route('pages.dashboard'));
});

test('user is not authorized', function () {
    $this->get(route('pages.password.confirm'))
        ->assertStatus(Response::HTTP_FOUND);
});

test('not found if the page address is incorrect', function () {
    $user = User::factory()->create();
    actingAs($user);

    $this->get('/confirm-passworde')
        ->assertStatus(Response::HTTP_NOT_FOUND);
});

test('the provided password is incorrect', function () {
    $user = User::factory()->create();
    actingAs($user);

    $response = $this->postJson(route('password.confirm'), ['password' => '1111'])
        ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

    $this->assertFalse($response->isRedirection());
});
