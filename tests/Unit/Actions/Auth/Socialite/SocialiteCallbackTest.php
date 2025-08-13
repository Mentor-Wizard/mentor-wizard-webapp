<?php

declare(strict_types=1);

use App\Actions\Auth\Socialite\SocialiteCallback;
use App\Enums\RoleEnum;
use App\Enums\SocialiteDriverEnum;
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

beforeEach(function (): void {
    Socialite::shouldReceive('driver')->andReturnSelf();
    Role::create(['name' => RoleEnum::USER]);
});

it('redirects authenticated user after social login', function ($driver): void {
    $socialUser = Mockery::mock();
    $socialUser->shouldReceive('getEmail')->andReturn('test@example.com');
    $socialUser->shouldReceive('getNickname')->andReturn('testuser');
    $socialUser->shouldReceive('getName')->andReturn('Test User');

    Socialite::shouldReceive('stateless->user')->andReturn($socialUser);

    $response = (new SocialiteCallback)->handle($driver->value);

    expect($response)->toBeInstanceOf(RedirectResponse::class)
        ->and($response->getTargetUrl())->toBe(route('pages.welcome'));

    assertDatabaseHas('users', ['email' => 'test@example.com']);

    assertAuthenticated();
})->with(SocialiteDriverEnum::cases());

it('creates user with getName() when getNickname() is empty', function ($driver): void {
    $socialUser = Mockery::mock();
    $socialUser->shouldReceive('getEmail')->andReturn('test@example.com');
    $socialUser->shouldReceive('getNickname')->andReturn(null);
    $socialUser->shouldReceive('getName')->andReturn('Test User');

    Socialite::shouldReceive('stateless->user')->andReturn($socialUser);

    $response = (new SocialiteCallback)->handle($driver->value);

    expect($response)->toBeInstanceOf(RedirectResponse::class)
        ->and($response->getTargetUrl())->toBe(route('pages.welcome'));

    assertDatabaseHas('users', ['email' => 'test@example.com']);

    assertAuthenticated();
})->with(SocialiteDriverEnum::cases());

it('creates user with getName() when getName() is empty', function ($driver): void {
    $socialUser = Mockery::mock();
    $socialUser->shouldReceive('getEmail')->andReturn('test@example.com');
    $socialUser->shouldReceive('getNickname')->andReturn('testuser');
    $socialUser->shouldReceive('getName')->andReturn(null);

    Socialite::shouldReceive('stateless->user')->andReturn($socialUser);

    $response = (new SocialiteCallback)->handle($driver->value);

    expect($response)->toBeInstanceOf(RedirectResponse::class)
        ->and($response->getTargetUrl())->toBe(route('pages.welcome'));

    assertDatabaseHas('users', ['email' => 'test@example.com']);

    assertAuthenticated();
})->with(SocialiteDriverEnum::cases());

it('logs an error and aborts if email is empty', function ($driver): void {
    $socialUser = Mockery::mock();
    $socialUser->shouldReceive('getEmail')->andReturn(null);

    Socialite::shouldReceive('stateless->user')->andReturn($socialUser);

    Log::shouldReceive('error')->once()->withArgs(fn (string $message, array $context): bool => $message === 'Email is empty, but required for login' && $context['driver'] === $driver->value);

    (new SocialiteCallback)->handle($driver->value);
})->with(SocialiteDriverEnum::cases())->throws(HttpException::class);
