<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

describe('ExternalCalendar route registration', function (): void {
    it('preserves the 11 external-calendar route names from before the module move', function (): void {
        $expectedNames = [
            'pages.settings.external-calendar',
            'external-calendar.connect.redirect',
            'external-calendar.connect.direct',
            'external-calendar.select',
            'external-calendar.disconnect',
            'external-calendar.retry',
            'external-calendar.sync-event',
            'external-calendar.rerun',
            'external-calendar.sync-integration',
            'external-calendar.log.acknowledge',
            'external-calendar.connect.callback',
        ];

        foreach ($expectedNames as $name) {
            expect(Route::has($name))->toBeTrue(sprintf('Route [%s] is not registered.', $name));
        }

        $actualNames = collect(Route::getRoutes())
            ->map(fn ($route) => $route->getName())
            ->filter(fn (?string $name): bool => $name !== null && (str_starts_with($name, 'external-calendar.') || $name === 'pages.settings.external-calendar'))
            ->values();

        expect($actualNames)->toHaveCount(11)
            ->and($actualNames->sort()->values()->all())->toEqual(collect($expectedNames)->sort()->values()->all());
    });

    it('keeps the connect.callback route outside the auth-protected group', function (): void {
        $route = Route::getRoutes()->getByName('external-calendar.connect.callback');

        expect($route)->not->toBeNull()
            ->and($route->gatherMiddleware())->not->toContain('auth');
    });

    it('keeps the settings index route inside the auth-protected group', function (): void {
        $route = Route::getRoutes()->getByName('pages.settings.external-calendar');

        expect($route)->not->toBeNull()
            ->and($route->gatherMiddleware())->toContain('auth');
    });
});
