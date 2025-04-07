<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Symfony\Component\HttpFoundation\Response;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\delete;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

describe('Destroy Profile', function () {
    it('deletes the user profile successfully', function () {
        $user = User::factory()->create();

        actingAs($user);

        delete(route('profile.destroy'), [
            'password' => 'password',
        ])
            ->assertStatus(Response::HTTP_FOUND)
            ->assertRedirect(route('login'));

        $this->assertGuest();

        assertDatabaseMissing('users', [
            'id' => $user->getKey(),
        ]);
    });

    it('invalidates the session after profile deletion', function () {
        $user = User::factory()->create();

        actingAs($user);

        session()->put('foo', 'bar');
        expect(session()->has('foo'))->toBeTrue();

        delete(route('profile.destroy'), [
            'password' => 'password',
        ])
            ->assertStatus(Response::HTTP_FOUND)
            ->assertRedirect(route('login'));

        $this->assertGuest();

        expect(session()->has('foo'))->toBeFalse();
    });

    it('regenerates session token after profile deletion', function () {
        $user = User::factory()->create();

        actingAs($user);

        $oldToken = session()->token();

        delete(route('profile.destroy'), [
            'password' => 'password',
        ])
            ->assertStatus(Response::HTTP_FOUND)
            ->assertRedirect(route('login'));

        $newToken = session()->token();

        expect($newToken)->not->toBe($oldToken);

        $this->assertGuest();
    });
});
