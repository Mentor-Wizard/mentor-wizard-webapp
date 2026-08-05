<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\ExternalCalendar\CalendarEventIntegrationsProvider;
use App\Contracts\ExternalCalendar\NullCalendarEventIntegrationsProvider;
use App\Enums\RoleEnum;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserSchedule;
use App\Policies\UserSchedulePolicy;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\ParallelTesting;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Modules\Chat\Models\Chat;
use Modules\Chat\Models\ChatMessage;
use Override;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    #[Override]
    public function register(): void
    {
        $this->app->bind(CalendarEventIntegrationsProvider::class, NullCalendarEventIntegrationsProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Date::use(CarbonImmutable::class);

        $this->configMorphMap();
        $this->configModels();
        $this->configDatabase();
        $this->configTesting();

        if ($this->app->isProduction()) {
            URL::forceHttps();
        }

        Gate::define('viewPulse', fn (User $user): bool => $user->hasAnyRole([RoleEnum::ADMIN, RoleEnum::SUPER_ADMIN]));
        Gate::policy(UserSchedule::class, UserSchedulePolicy::class);
        Vite::prefetch(concurrency: 3);

        $this->configRateLimiters();
    }

    private function configRateLimiters(): void
    {
        RateLimiter::for('calendar-connect', fn (Request $request): Limit => Limit::perMinute(10)->by($request->user()?->getKey()));

        RateLimiter::for('calendar-retry', fn (Request $request): Limit => Limit::perMinute(5)->by($request->user()?->getKey()));

        RateLimiter::for('calendar-sync', fn (Request $request): Limit => Limit::perMinute(20)->by($request->user()?->getKey()));
    }

    /**
     * Registers a namespace-independent, enforced morph map for every model
     * that participates in a polymorphic relation (HasMedia via
     * spatie/laravel-medialibrary, Notifiable via spatie/laravel-permission's
     * model_has_roles/model_has_permissions, and notifications.notifiable_type).
     *
     * `enforceMorphMap()` (not the permissive `morphMap()`) is deliberate: any
     * future HasMedia/Notifiable/role-bearing model that forgets to register an
     * alias here fails loudly (ClassMorphViolationException) instead of quietly
     * storing a long FQCN that breaks the moment the class moves into a module.
     */
    private function configMorphMap(): void
    {
        Relation::enforceMorphMap([
            'user'         => User::class,
            'user_profile' => UserProfile::class,
            'chat'         => Chat::class,
            'chat_message' => ChatMessage::class,
        ]);
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
