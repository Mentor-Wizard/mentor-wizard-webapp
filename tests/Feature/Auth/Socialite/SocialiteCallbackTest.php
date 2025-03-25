<?php

declare(strict_types=1);

use App\Actions\Auth\Socialite\SocialiteCallback;
use App\Enums\SocialiteDriver;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Symfony\Component\HttpFoundation\Response;

mutates(SocialiteCallback::class);

describe('Socialite Authentication', function () {

    beforeEach(function () {
        $this->seed(RoleSeeder::class);
    });

    it('allows user to login via social provider', function ($driver) {
        $socialiteUser = Mockery::mock(SocialiteUser::class);
        $socialiteUser
            ->shouldReceive('getEmail')->andReturn('test@example.com')
            ->shouldReceive('getNickname')->andReturn('testuser')
            ->shouldReceive('getName')->andReturn('Test User');

        Socialite::shouldReceive('driver->stateless->user')
            ->once()
            ->andReturn($socialiteUser);

        $response = $this->get(route('auth.socialite.callback', ['driver' => $driver->value]))
            ->assertRedirect(route('pages.welcome'));

        expect(Auth::user()->email)->toBe('test@example.com')
            ->and(Auth::user()->username)->toBe('testuser');

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'username' => 'testuser',
        ]);
    })->with(SocialiteDriver::cases());

    it('fails when email is empty', function ($driver) {
        $socialiteUser = Mockery::mock(SocialiteUser::class);
        $socialiteUser
            ->shouldReceive('getEmail')->andReturn('')
            ->shouldReceive('getNickname')->andReturn('testuser')
            ->shouldReceive('getName')->andReturn('Test User');

        Socialite::shouldReceive('driver->stateless->user')
            ->once()
            ->andReturn($socialiteUser);

        $this->get(route('auth.socialite.callback', ['driver' => $driver->value]))
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

        $this->assertDatabaseMissing('users', [
            'username' => 'testuser',
        ]);
    })->with(SocialiteDriver::cases());
});
