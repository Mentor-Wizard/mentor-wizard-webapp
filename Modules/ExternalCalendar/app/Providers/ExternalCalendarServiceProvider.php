<?php

declare(strict_types=1);

namespace Modules\ExternalCalendar\Providers;

use App\Contracts\ExternalCalendar\CalendarEventIntegrationsProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Modules\Calendar\Events\CalendarEventCancelled;
use Modules\Calendar\Events\CalendarEventConfirmed;
use Modules\Calendar\Events\CalendarEventContentChanged;
use Modules\Calendar\Events\CalendarEventDeleting;
use Modules\ExternalCalendar\Listeners\SyncExternalCalendarOnCalendarEventChange;
use Modules\ExternalCalendar\Models\ExternalCalendarEvent;
use Modules\ExternalCalendar\Models\ExternalCalendarEventLog;
use Modules\ExternalCalendar\Models\UserCalendarIntegration;
use Modules\ExternalCalendar\Policies\ExternalCalendarEventLogPolicy;
use Modules\ExternalCalendar\Policies\ExternalCalendarEventPolicy;
use Modules\ExternalCalendar\Policies\UserCalendarIntegrationPolicy;
use Modules\ExternalCalendar\Support\EloquentCalendarEventIntegrationsProvider;
use Nwidart\Modules\Support\ModuleServiceProvider;
use Override;

class ExternalCalendarServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    #[Override]
    protected string $name = 'ExternalCalendar';

    /**
     * The lowercase version of the module name.
     */
    #[Override]
    protected string $nameLower = 'externalcalendar';

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

        Gate::policy(UserCalendarIntegration::class, UserCalendarIntegrationPolicy::class);
        Gate::policy(ExternalCalendarEvent::class, ExternalCalendarEventPolicy::class);
        Gate::policy(ExternalCalendarEventLog::class, ExternalCalendarEventLogPolicy::class);

        Event::listen(CalendarEventConfirmed::class, [SyncExternalCalendarOnCalendarEventChange::class, 'handleConfirmed']);
        Event::listen(CalendarEventCancelled::class, [SyncExternalCalendarOnCalendarEventChange::class, 'handleCancelled']);
        Event::listen(CalendarEventContentChanged::class, [SyncExternalCalendarOnCalendarEventChange::class, 'handleContentChanged']);
        Event::listen(CalendarEventDeleting::class, [SyncExternalCalendarOnCalendarEventChange::class, 'handleDeleting']);

        $this->app->bind(CalendarEventIntegrationsProvider::class, EloquentCalendarEventIntegrationsProvider::class);
    }
}
