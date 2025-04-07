<?php

declare(strict_types=1);

use App\Http\Requests\Auth\Login\LoginRequest;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

describe('LoginRequest Authentication', function () {
    describe('Authentication Scenarios', function () {
        it('successfully authorizes request', function () {
            $request = new LoginRequest;
            expect($request->authorize())->toBeTrue();
        });

        it('defines correct validation rules', function () {
            $request = new LoginRequest;
            $rules = $request->rules();

            expect($rules)->toHaveKeys(['email', 'password'])
                ->and($rules['email'])->toContain('required', 'string', 'email')
                ->and($rules['password'])->toContain('required', 'string');
        });
    });

    describe('Successful Authentication', function () {
        it('authenticates with valid credentials', function () {
            $request = Mockery::mock(LoginRequest::class)
                ->makePartial()
                ->shouldAllowMockingProtectedMethods();

            $request->shouldReceive('only')
                ->with('email', 'password')
                ->andReturn([
                    'email' => 'test@example.com',
                    'password' => 'password',
                ]);
            $request->shouldReceive('boolean')
                ->with('remember')
                ->andReturn(false);
            $request->shouldReceive('throttleKey')
                ->andReturn('throttle_key');

            Auth::shouldReceive('attempt')
                ->with(['email' => 'test@example.com', 'password' => 'password'], false)
                ->andReturn(true);

            RateLimiter::shouldReceive('tooManyAttempts')
                ->with('throttle_key', 5)
                ->andReturn(false);

            RateLimiter::shouldReceive('clear')
                ->with('throttle_key')
                ->once();

            $request->authenticate();
        });
    });

    describe('Failed Authentication', function () {
        it('throws validation exception on failed login', function () {
            $request = Mockery::mock(LoginRequest::class)
                ->makePartial()
                ->shouldAllowMockingProtectedMethods();

            $request->shouldReceive('only')
                ->with('email', 'password')
                ->andReturn([
                    'email' => 'test@example.com',
                    'password' => 'wrong_password',
                ]);
            $request->shouldReceive('boolean')
                ->with('remember')
                ->andReturn(false);
            $request->shouldReceive('throttleKey')
                ->andReturn('throttle_key');
            $request->shouldReceive('ip')
                ->andReturn('127.0.0.1');

            Auth::shouldReceive('attempt')
                ->with(['email' => 'test@example.com', 'password' => 'wrong_password'], false)
                ->andReturn(false);

            RateLimiter::shouldReceive('tooManyAttempts')
                ->with('throttle_key', 5)
                ->andReturn(false);

            RateLimiter::shouldReceive('hit')
                ->with('throttle_key')
                ->once();

            expect(fn () => $request->authenticate())
                ->toThrow(ValidationException::class);
        });
    });

    describe('Rate Limiting', function () {
        it('prevents login when rate limit exceeded', function () {
            $request = Mockery::mock(LoginRequest::class)
                ->makePartial()
                ->shouldAllowMockingProtectedMethods();

            $request->shouldReceive('throttleKey')
                ->andReturn('throttle_key');
            $request->shouldReceive('ip')
                ->andReturn('127.0.0.1');
            $request->shouldReceive('string')
                ->with('email')
                ->andReturn('test@example.com');

            RateLimiter::shouldReceive('tooManyAttempts')
                ->with('throttle_key', 5)
                ->andReturn(true);

            RateLimiter::shouldReceive('availableIn')
                ->with('throttle_key')
                ->andReturn(60);

            Event::fake();

            expect(fn () => $request->ensureIsNotRateLimited())
                ->toThrow(ValidationException::class);

            Event::assertDispatched(Lockout::class);
        });

        it('allows login when rate limit not exceeded', function () {
            $request = Mockery::mock(LoginRequest::class)
                ->makePartial()
                ->shouldAllowMockingProtectedMethods();

            $request->shouldReceive('throttleKey')
                ->andReturn('throttle_key');

            RateLimiter::shouldReceive('tooManyAttempts')
                ->with('throttle_key', 5)
                ->andReturn(false);

            $request->ensureIsNotRateLimited();

            expect(true)->toBeTrue();
        });
    });

    describe('Throttle Key Generation', function () {
        it('generates correct throttle key', function () {
            $request = Mockery::mock(LoginRequest::class)
                ->makePartial()
                ->shouldAllowMockingProtectedMethods();

            $request->shouldReceive('string')
                ->with('email')
                ->andReturn('TEST@EXAMPLE.COM');
            $request->shouldReceive('ip')
                ->andReturn('127.0.0.1');

            $throttleKey = $request->throttleKey();
            expect($throttleKey)->toBe('test@example.com|127.0.0.1');
        });
    });
});
