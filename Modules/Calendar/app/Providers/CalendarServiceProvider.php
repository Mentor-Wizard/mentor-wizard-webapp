<?php

declare(strict_types=1);

namespace Modules\Calendar\Providers;

use Illuminate\Support\Facades\Gate;
use Modules\Calendar\Models\CalendarEvent;
use Modules\Calendar\Policies\CalendarEventPolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;
use Override;

class CalendarServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    #[Override]
    protected string $name = 'Calendar';

    /**
     * The lowercase version of the module name.
     */
    #[Override]
    protected string $nameLower = 'calendar';

    /**
     * Provider classes to register.
     *
     * @var string[]
     */
    #[Override]
    protected array $providers = [
        RouteServiceProvider::class,
    ];

    #[Override]
    public function boot(): void
    {
        parent::boot();

        Gate::policy(CalendarEvent::class, CalendarEventPolicy::class);
    }
}
