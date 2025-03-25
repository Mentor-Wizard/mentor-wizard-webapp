<?php

declare(strict_types=1);

use App\Actions\Auth\Login\Login;
use App\Http\Requests\Auth\Login\LoginRequest;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Session\SessionManager;
use Illuminate\Support\MessageBag;
use Illuminate\Validation\ValidationException;

mutates(Login::class);

describe('Login Action', function () {

    it('redirects to dashboard after successful login', function () {
        $request = Mockery::mock(LoginRequest::class);
        $request->shouldReceive('authenticate')->once();

        $session = Mockery::mock(SessionManager::class);
        $session->shouldReceive('regenerate')->once();
        $request->shouldReceive('session')->once()->andReturn($session);

        $loginAction = new Login;
        $response = $loginAction->handle($request);

        expect($response)->toBeInstanceOf(RedirectResponse::class)
            ->and($response->getTargetUrl())->toBe(route('pages.dashboard'));
    });

    it('handles failed login attempt', function () {
        $request = Mockery::mock(LoginRequest::class);

        $validator = Mockery::mock(Validator::class);
        $validator->shouldReceive('errors')->once()->andReturn(new MessageBag([]));

        $translator = Mockery::mock(Translator::class);
        $validator->shouldReceive('getTranslator')->once()->andReturn($translator);
        $translator->shouldReceive('get')
            ->andReturn('Validation failed');

        $request->shouldReceive('authenticate')->andThrow(new ValidationException($validator));

        $loginAction = new Login;

        expect(function () use ($loginAction, $request) {
            $loginAction->handle($request);
        })->toThrow(ValidationException::class);
    });
});
