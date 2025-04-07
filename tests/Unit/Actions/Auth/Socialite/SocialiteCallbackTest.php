<?php

use App\Actions\Auth\Socialite\SocialiteCallback;
use App\Enums\RoleEnum;
use App\Enums\RoleGuardEnum;
use App\Enums\SocialiteDriver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpKernel\Exception\HttpException;
use function Pest\Laravel\assertAuthenticated;
use function Pest\Laravel\assertDatabaseHas;

uses(RefreshDatabase::class);

mutates(SocialiteCallback::class);

beforeEach(function () {
    Socialite::shouldReceive('driver')->andReturnSelf();
    Role::create(['name' => RoleEnum::USER, 'guard_name' => RoleGuardEnum::USER]);
});

it('redirects authenticated user after social login', function ($driver) {
    $socialUser = Mockery::mock();
    $socialUser->shouldReceive('getEmail')->andReturn('test@example.com');
    $socialUser->shouldReceive('getNickname')->andReturn('testuser');
    $socialUser->shouldReceive('getName')->andReturn('Test User');

    Socialite::shouldReceive('stateless->user')->andReturn($socialUser);

    $response = new SocialiteCallback()->handle($driver->value);

    expect($response)->toBeInstanceOf(RedirectResponse::class)
        ->and($response->getTargetUrl())->toBe(route('pages.welcome'));

    assertDatabaseHas('users', ['email' => 'test@example.com']);

    assertAuthenticated();
})->with(SocialiteDriver::cases());

it('creates user with getName() when getNickname() is empty', function ($driver) {
    $socialUser = Mockery::mock();
    $socialUser->shouldReceive('getEmail')->andReturn('test@example.com');
    $socialUser->shouldReceive('getNickname')->andReturn(null);
    $socialUser->shouldReceive('getName')->andReturn('Test User');

    Socialite::shouldReceive('stateless->user')->andReturn($socialUser);

    $response = new SocialiteCallback()->handle($driver->value);

    expect($response)->toBeInstanceOf(RedirectResponse::class)
        ->and($response->getTargetUrl())->toBe(route('pages.welcome'));

    assertDatabaseHas('users', ['email' => 'test@example.com']);

    assertAuthenticated();
})->with(SocialiteDriver::cases());

it('creates user with getName() when getName() is empty', function ($driver) {
    $socialUser = Mockery::mock();
    $socialUser->shouldReceive('getEmail')->andReturn('test@example.com');
    $socialUser->shouldReceive('getNickname')->andReturn('testuser');
    $socialUser->shouldReceive('getName')->andReturn(null);

    Socialite::shouldReceive('stateless->user')->andReturn($socialUser);

    $response = new SocialiteCallback()->handle($driver->value);

    expect($response)->toBeInstanceOf(RedirectResponse::class)
        ->and($response->getTargetUrl())->toBe(route('pages.welcome'));

    assertDatabaseHas('users', ['email' => 'test@example.com']);

    assertAuthenticated();
})->with(SocialiteDriver::cases());

it('logs an error and aborts if email is empty', function ($driver) {
    $socialUser = Mockery::mock();
    $socialUser->shouldReceive('getEmail')->andReturn(null);

    Socialite::shouldReceive('stateless->user')->andReturn($socialUser);

    Log::shouldReceive('error')->once()->withArgs(function (string $message, array $context) use ($driver) {
        return $message === 'Email is empty, but required for login' && $context['driver'] === $driver->value;
    });

    new SocialiteCallback()->handle($driver->value);
})->with(SocialiteDriver::cases())->throws(HttpException::class);
