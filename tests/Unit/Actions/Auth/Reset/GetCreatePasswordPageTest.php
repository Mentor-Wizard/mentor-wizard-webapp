<?php

declare(strict_types=1);

use App\Actions\Auth\Reset\GetCreatePasswordPage;
use Illuminate\Http\Request;
use Illuminate\Routing\RouteCollection;
use Illuminate\Support\Arr;
use Inertia\Response;

describe('GetCreatePasswordPage', function (): void {
    it('renders reset password page with email and token', function (): void {
        $mockRouteCollection = Mockery::mock(RouteCollection::class);
        $mockRouteCollection->shouldReceive('getRoutesByName')->andReturn([]);
        Route::shouldReceive('has')
            ->with('password.reset')
            ->andReturn(true);
        Route::shouldReceive('getRoutes')
            ->andReturn($mockRouteCollection);

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('route')
            ->with('token')
            ->once()
            ->andReturn('test-reset-token');
        $request->shouldReceive('get')
            ->with('email')
            ->once()
            ->andReturn('test@example.com');

        session(['status' => 'test_message']);

        $action = new GetCreatePasswordPage;

        $result = $action->handle($request);
        $resultData = $result->toResponse(request())->getOriginalContent();

        expect($result)->toBeInstanceOf(Response::class)
            ->and(Arr::get($resultData->getData(), 'page.component'))->toBe('Auth/ResetPassword')
            ->and(Arr::get($resultData->getData(), 'page.props'))->toEqual([
                'email' => 'test@example.com',
                'token' => 'test-reset-token',
            ]);
    });
});
