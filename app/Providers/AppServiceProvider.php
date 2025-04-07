<?php

declare(strict_types=1);

namespace App\Providers;

use App\Enums\RoleEnum;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void {}

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configModels();
        $this->configDatabase();

        if ($this->app->isProduction()) {
            URL::forceHttps();
        }

        Gate::define('viewPulse', function (User $user) {
            return $user->hasAnyRole([RoleEnum::ADMIN, RoleEnum::SUPER_ADMIN]);
        });

        Vite::prefetch(concurrency: 3);
    }

    private function configModels(): void
    {
        Model::shouldBeStrict();
        Model::preventLazyLoading();
        Model::preventAccessingMissingAttributes();
        Model::preventSilentlyDiscardingAttributes();
    }

    private function configDatabase(): void
    {
        DB::prohibitDestructiveCommands($this->app->isProduction());
    }
}
