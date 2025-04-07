<?php

declare(strict_types=1);

use App\Actions\Auth\Socialite\SocialiteRedirect;
use App\Enums\SocialiteDriver;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\Response;

mutates(SocialiteRedirect::class);

describe('Socialite Redirect', function () {

    it('redirects to valid social driver', function ($driver) {
        $socialiteMock = Mockery::mock(Provider::class);
        $socialiteMock
            ->shouldReceive('redirect')
            ->once()
            ->andReturn(redirect('https://oauth.mockprovider.com'));

        Socialite::shouldReceive('driver')
            ->with($driver->value)
            ->once()
            ->andReturn($socialiteMock);

        $this->get(route('auth.socialite.redirect', ['driver' => $driver->value]))
            ->assertRedirect()
            ->assertStatus(Response::HTTP_FOUND);
    })->with(SocialiteDriver::cases());

    it('fails with invalid social driver', function () {
        $invalidDriver = 'invalid_driver';

        Log::shouldReceive('error')
            ->once()
            ->with('Invalid socialite driver', ['driver' => $invalidDriver]);

        $this->get(route('auth.socialite.redirect', ['driver' => $invalidDriver]))
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    });
});
