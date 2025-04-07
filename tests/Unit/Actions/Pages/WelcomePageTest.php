<?php

declare(strict_types=1);

use App\Actions\Pages\WelcomePage;
use Illuminate\Foundation\Application;
use Illuminate\Routing\RouteCollection;
use Inertia\Response;

mutates(WelcomePage::class);

describe('WelcomePage Action', function () {

    it('returns correct Inertia response', function () {
        $mockRouteCollection = Mockery::mock(RouteCollection::class);
        $mockRouteCollection->shouldReceive('getRoutesByName')->andReturn([]);

        Route::shouldReceive('has')
            ->with('login')
            ->once()
            ->andReturn(true);
        Route::shouldReceive('has')
            ->with('register')
            ->once()
            ->andReturn(true);

        Route::shouldReceive('getRoutes')->andReturn($mockRouteCollection);

        $action = new WelcomePage;
        $result = $action->handle();
        $resultData = $result->toResponse(request())->getOriginalContent();

        expect($result)->toBeInstanceOf(Response::class)
            ->and(Arr::get($resultData->getData(), 'page.component'))->toBe('Welcome')
            ->and(Arr::get($resultData->getData(), 'page.props'))->toEqual([
                'canLogin' => true,
                'canRegister' => true,
                'phpVersion' => PHP_VERSION,
                'laravelVersion' => Application::VERSION,
            ]);
    });
});
