<?php

declare(strict_types=1);

use App\Actions\Auth\ConfirmPassword;
use App\Http\Requests\Auth\ConfirmPasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Redirect;

mutates(ConfirmPassword::class);

describe('ConfirmPassword Action', function () {
    it('confirms password and redirects to dashboard', function () {
        $mockRequest = Mockery::mock(ConfirmPasswordRequest::class);
        $mockSession = Mockery::mock(Store::class);
        $action = new ConfirmPassword;

        $mockRequest
            ->shouldReceive('session')
            ->andReturn($mockSession);

        $mockSession
            ->shouldReceive('put')
            ->with('auth.password_confirmed_at', Mockery::type('int'))
            ->once();

        $redirectMock = Mockery::mock(RedirectResponse::class);
        Redirect::shouldReceive('intended')
            ->with(route('pages.dashboard'))
            ->once()
            ->andReturn($redirectMock);

        $response = $action->handle($mockRequest);

        expect($response)->toBeInstanceOf(RedirectResponse::class);
    });

    it('stores password confirmation timestamp in session', function () {
        $mockRequest = Mockery::mock(ConfirmPasswordRequest::class);
        $mockSession = Mockery::mock(Store::class);
        $action = new ConfirmPassword;

        $mockRequest
            ->shouldReceive('session')
            ->andReturn($mockSession);

        $mockSession
            ->shouldReceive('put')
            ->with('auth.password_confirmed_at', Mockery::type('int'))
            ->once();

        Redirect::shouldReceive('intended')
            ->andReturn(new RedirectResponse('/'));

        $action->handle($mockRequest);
    });
});
