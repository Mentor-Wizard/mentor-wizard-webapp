<?php

declare(strict_types=1);

use App\Actions\Auth\Socialite\SocialiteRedirect;
use App\Enums\SocialiteDriver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpKernel\Exception\HttpException;

mutates(SocialiteRedirect::class);

beforeEach(function () {
    Socialite::shouldReceive('driver')->andReturnSelf();
});

it('redirects to socialite driver if valid driver is provided', function ($driver) {
    Socialite::shouldReceive('redirect')->once()->andReturn(new RedirectResponse('/auth/'.($driver->value).'/redirect'));

    $response = (new SocialiteRedirect)->handle($driver->value);

    expect($response)->toBeInstanceOf(RedirectResponse::class);
    expect($response->getTargetUrl())->toBe('/auth/'.($driver->value).'/redirect');
})->with(SocialiteDriver::cases());

it('logs an error and aborts if an invalid driver is provided', function () {
    $invalidDriver = 'invalid';

    Log::shouldReceive('error')->once()->withArgs(function (string $message, array $context) use ($invalidDriver) {
        return $message === 'Invalid socialite driver' && $context['driver'] === $invalidDriver;
    });

    (new SocialiteRedirect)->handle($invalidDriver);
})->throws(HttpException::class);
