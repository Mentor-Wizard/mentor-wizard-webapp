<?php

declare(strict_types=1);

namespace Modules\Chat\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;
use Override;

class RouteServiceProvider extends ServiceProvider
{
    protected string $name = 'Chat';

    /**
     * Called before routes are registered.
     *
     * Register any model bindings or pattern based filters.
     */
    #[Override]
    public function boot(): void
    {
        parent::boot();
    }

    /**
     * Define the routes for the application.
     */
    public function map(): void
    {
        $this->mapWebRoutes();
    }

    /**
     * Define the "web" routes for the application.
     *
     * These routes all receive session state, CSRF protection, etc.
     */
    protected function mapWebRoutes(): void
    {
        Route::middleware('web')->group(module_path($this->name, '/routes/web.php'));
    }
}
