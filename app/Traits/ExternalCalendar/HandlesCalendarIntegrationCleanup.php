<?php

declare(strict_types=1);

namespace App\Traits\ExternalCalendar;

use App\Enums\CalendarProviderEnum;
use App\Models\User;
use App\Models\UserCalendarIntegration;

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
