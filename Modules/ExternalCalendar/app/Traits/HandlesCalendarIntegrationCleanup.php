<?php

declare(strict_types=1);

namespace Modules\ExternalCalendar\Traits;

use App\Models\User;
use Modules\ExternalCalendar\Enums\CalendarProviderEnum;
use Modules\ExternalCalendar\Models\UserCalendarIntegration;

trait HandlesCalendarIntegrationCleanup
{
    protected function cleanupIntegration(?User $user, ?CalendarProviderEnum $provider): void
    {
        if (! $user instanceof User || ! $provider instanceof CalendarProviderEnum) {
            return;
        }

        UserCalendarIntegration::query()
            ->where('user_id', $user->getKey())
            ->where('provider', $provider)
            ->delete();
    }
}
