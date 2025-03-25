<?php

declare(strict_types=1);

use App\Actions\Pages\DashboardPage;
use Illuminate\Routing\RouteCollection;
use Inertia\Response;

mutates(DashboardPage::class);

describe('DashboardPage Action', function () {

    it('returns correct Inertia response', function () {
        $mockRouteCollection = Mockery::mock(RouteCollection::class);
        $mockRouteCollection->shouldReceive('getRoutesByName')->andReturn([]);

        Route::shouldReceive('getRoutes')->andReturn($mockRouteCollection);

        $action = new DashboardPage;
        $result = $action->handle();
        $resultData = $result->toResponse(request())->getOriginalContent();

        expect($result)->toBeInstanceOf(Response::class)
            ->and(Arr::get($resultData->getData(), 'page.component'))->toBe('Dashboard')
            ->and(Arr::get($resultData->getData(), 'page.props'))->toBeEmpty();
    });
});
