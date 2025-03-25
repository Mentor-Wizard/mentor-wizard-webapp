<?php

declare(strict_types=1);

use App\Actions\Auth\Login\GetLoginPage;
use Illuminate\Routing\RouteCollection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;
use Inertia\Response;

mutates(GetLoginPage::class);

describe('GetLoginPage Action', function () {

    it('returns correct Inertia response', function () {
        $mockRouteCollection = Mockery::mock(RouteCollection::class);
        $mockRouteCollection->shouldReceive('getRoutesByName')->andReturn([]);

        Route::shouldReceive('has')
            ->with('password.request')
            ->andReturn(true);
        Route::shouldReceive('getRoutes')
            ->andReturn($mockRouteCollection);

        session(['status' => 'test_message']);

        $action = new GetLoginPage;
        $result = $action->handle();
        $resultData = $result->toResponse(request())->getOriginalContent();

        expect($result)->toBeInstanceOf(Response::class)
            ->and(Arr::get($resultData->getData(), 'page.component'))->toBe('Auth/Login')
            ->and(Arr::get($resultData->getData(), 'page.props'))->toEqual([
                'canResetPassword' => true,
                'status' => 'test_message',
            ]);
    });

    it('returns correct Inertia response when password reset is not available', function () {
        $mockRouteCollection = Mockery::mock(RouteCollection::class);
        $mockRouteCollection->shouldReceive('getRoutesByName')->andReturn([]);

        Route::shouldReceive('has')
            ->with('password.request')
            ->andReturn(false);
        Route::shouldReceive('getRoutes')
            ->andReturn($mockRouteCollection);

        session(['status' => 'test_message']);

        $action = new GetLoginPage;
        $result = $action->handle();
        $resultData = $result->toResponse(request())->getOriginalContent();

        expect($result)->toBeInstanceOf(Response::class)
            ->and(Arr::get($resultData->getData(), 'page.component'))->toBe('Auth/Login')
            ->and(Arr::get($resultData->getData(), 'page.props'))->toEqual([
                'canResetPassword' => false,
                'status' => 'test_message',
            ]);
    });
});
