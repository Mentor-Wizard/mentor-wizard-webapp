<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

describe('Auth route registration', function (): void {
    it('preserves the 16 named auth routes from before the module move', function (): void {
        $expectedNames = [
            'register',
            'login',
            'login.attempt',
            'password.request',
            'password.email',
            'password.reset',
            'password.store',
            'auth.socialite.redirect',
            'auth.socialite.callback',
            'verification.notice',
            'verification.send',
            'verification.verify',
            'pages.password.confirm',
            'password.confirm',
            'password.update',
            'logout',
        ];

        foreach ($expectedNames as $name) {
            expect(Route::has($name))->toBeTrue(sprintf('Route [%s] is not registered.', $name));
        }
    });

    it('registers the unnamed POST /register route', function (): void {
        $route = collect(Route::getRoutes())
            ->first(fn ($route): bool => $route->uri() === 'register' && in_array('POST', $route->methods(), true));

        expect($route)->not->toBeNull()
            ->and($route->getName())->toStartWith('generated::');
    });

    it('keeps guest-only routes outside the auth-protected group', function (): void {
        $route = Route::getRoutes()->getByName('login');

        expect($route)->not->toBeNull()
            ->and($route->gatherMiddleware())->toContain('guest')
            ->and($route->gatherMiddleware())->not->toContain('auth');
    });

    it('keeps session-protected routes inside the auth-protected group', function (): void {
        $route = Route::getRoutes()->getByName('logout');

        expect($route)->not->toBeNull()
            ->and($route->gatherMiddleware())->toContain('auth')
            ->and($route->gatherMiddleware())->not->toContain('guest');
    });

    it('throttles email verification routes', function (): void {
        $sendRoute = Route::getRoutes()->getByName('verification.send');
        $verifyRoute = Route::getRoutes()->getByName('verification.verify');

        expect($sendRoute->gatherMiddleware())->toContain('throttle:6,1')
            ->and($verifyRoute->gatherMiddleware())->toContain('throttle:6,1')
            ->and($verifyRoute->gatherMiddleware())->toContain('signed');
    });
});
