<?php

declare(strict_types=1);

namespace App\Providers;

use App\Enums\RoleEnum;
use App\Listeners\DispatchPaymentWebhookJob;
use App\Models\CalendarEvent;
use App\Models\ExternalCalendarEvent;
use App\Models\ExternalCalendarEventLog;
use App\Models\Payment;
use App\Models\User;
use App\Models\UserCalendarIntegration;
use App\Models\UserSchedule;
use App\Policies\CalendarEventPolicy;
use App\Policies\ExternalCalendarEventLogPolicy;
use App\Policies\ExternalCalendarEventPolicy;
use App\Policies\PaymentPolicy;
use App\Policies\UserCalendarIntegrationPolicy;
use App\Policies\UserSchedulePolicy;
use AratKruglik\WayForPay\Events\WayForPayCallbackReceived;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\ParallelTesting;
use Illuminate\Support\Facades\RateLimiter;
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

        RateLimiter::for('chat-send', fn (Request $request) => Limit::perMinute(60)->by($request->user()?->getKey() ?: $request->ip()));
        RateLimiter::for('chat-create', fn (Request $request) => Limit::perMinute(10)->by($request->user()?->getKey() ?: $request->ip()));

        if ($this->app->isProduction()) {
            URL::forceHttps();
        }

        Gate::define('viewPulse', fn (User $user): bool => $user->hasAnyRole([RoleEnum::ADMIN, RoleEnum::SUPER_ADMIN]));
        Gate::define('initiate-payment', [PaymentPolicy::class, 'initiate']);
        Gate::policy(CalendarEvent::class, CalendarEventPolicy::class);
        Gate::policy(UserSchedule::class, UserSchedulePolicy::class);
        Gate::policy(ExternalCalendarEventLog::class, ExternalCalendarEventLogPolicy::class);
        Gate::policy(UserCalendarIntegration::class, UserCalendarIntegrationPolicy::class);
        Gate::policy(ExternalCalendarEvent::class, ExternalCalendarEventPolicy::class);
        Gate::policy(Payment::class, PaymentPolicy::class);

        Event::listen(WayForPayCallbackReceived::class, DispatchPaymentWebhookJob::class);

        Vite::prefetch(concurrency: 3);

        $this->configRateLimiters();
    }

    private function configRateLimiters(): void
    {
        RateLimiter::for('calendar-connect', fn (Request $request): Limit => Limit::perMinute(10)->by($request->user()?->getKey()));

        RateLimiter::for('calendar-retry', fn (Request $request): Limit => Limit::perMinute(5)->by($request->user()?->getKey()));

        RateLimiter::for('calendar-sync', fn (Request $request): Limit => Limit::perMinute(20)->by($request->user()?->getKey()));
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
