<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\TelescopeApplicationServiceProvider;

class TelescopeServiceProvider extends TelescopeApplicationServiceProvider
{
    /**
     * Register any application services.
     */
    #[\Override]
    public function register(): void
    {
        Telescope::night();

        $this->hideSensitiveRequestDetails();

        $isLocal = $this->app->environment('local', 'testing', 'ci');

        Telescope::filter(function (IncomingEntry $entry) use ($isLocal): bool {
            if ($entry->type === 'request' && $entry->content['uri'] === '/up') {
                return false;
            }

            if ($entry->type === 'view' && isset($entry->content['name']) && str_contains((string) $entry->content['name'], 'health-up.blade.php')) {
                return false;
            }

            return match (true) {
                $isLocal,
                $entry->isReportableException(),
                $entry->isFailedRequest(),
                $entry->isFailedJob(),
                $entry->isScheduledTask(),
                $entry->hasMonitoredTag() => true,
                default => false,
            };
        });
    }

    /**
     * Prevent sensitive request details from being logged by Telescope.
     */
    protected function hideSensitiveRequestDetails(): void
    {
        if ($this->app->environment('local', 'testing', 'ci')) {
            return;
        }

        Telescope::hideRequestParameters(['_token']);

        Telescope::hideRequestHeaders([
            'cookie',
            'x-csrf-token',
            'x-xsrf-token',
        ]);
    }

    /**
     * Register the Telescope gate.
     *
     * This gate determines who can access Telescope in non-local environments.
     */
    #[\Override]
    protected function gate(): void
    {
        Gate::define('viewTelescope', fn (?User $user) => $this->app->environment('local', 'testing', 'ci'));
    }
}
