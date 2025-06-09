<?php

declare(strict_types=1);

use App\Actions\Pages\WelcomePage;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Application;
use Illuminate\Routing\RouteCollection;
use Inertia\Response;

mutates(WelcomePage::class);

describe('WelcomePage Action', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
    });

    it('returns correct Inertia response', function (): void {
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
            ->and(Arr::get($resultData->getData(), 'page.props'))->toMatchArray([
                'canLogin'       => true,
                'canRegister'    => true,
                'phpVersion'     => PHP_VERSION,
                'laravelVersion' => Application::VERSION,
            ])
            ->and(fn ($result): Pest\Mixins\Expectation => expect(Arr::get($resultData->getData(), 'page.props.mentors'))->toBeArray());
    });
});
