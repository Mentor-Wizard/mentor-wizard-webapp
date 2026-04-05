<?php

declare(strict_types=1);

namespace App\Providers;

use App\Enums\RoleEnum;
use App\Models\CalendarEvent;
use App\Models\User;
use App\Models\UserSchedule;
use App\Policies\CalendarEventPolicy;
use App\Policies\UserSchedulePolicy;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\ParallelTesting;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Override;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    #[Override]
    public function register(): void {}

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Date::use(CarbonImmutable::class);

        $this->configModels();
        $this->configDatabase();
        $this->configTesting();

        //        if ($this->app->isProduction()) {
        URL::forceHttps();
        //        }

        Gate::define('viewPulse', fn (User $user): bool => $user->hasAnyRole([RoleEnum::ADMIN, RoleEnum::SUPER_ADMIN]));
        Gate::policy(CalendarEvent::class, CalendarEventPolicy::class);
        Gate::policy(UserSchedule::class, UserSchedulePolicy::class);
        Vite::prefetch(concurrency: 3);
    }

    private function configModels(): void
    {
        Model::shouldBeStrict();
    }

    private function configDatabase(): void
    {
        DB::prohibitDestructiveCommands($this->app->isProduction());
    }

    private function configTesting(): void
    {
        ParallelTesting::setUpProcess(function (int $token): void {
            config(['permission.cache.key' => 'spatie.permission.cache.'.$token]);
        });
    }
}
