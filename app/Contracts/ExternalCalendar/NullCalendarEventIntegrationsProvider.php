<?php

declare(strict_types=1);

namespace App\Contracts\ExternalCalendar;

use App\Models\User;

/**
 * Null-object fallback bound in `AppServiceProvider`, so `Modules\Calendar`
 * remains functional (`externalIntegrations` resolves to an empty list) even
 * when `Modules\ExternalCalendar` is disabled or not yet booted.
 */
final class NullCalendarEventIntegrationsProvider implements CalendarEventIntegrationsProvider
{
    public function forCalendarEvent(int $calendarEventId, User $user): array
    {
        return [];
    }
}
