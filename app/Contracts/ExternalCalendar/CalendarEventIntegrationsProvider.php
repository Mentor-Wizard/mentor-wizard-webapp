<?php

declare(strict_types=1);

namespace App\Contracts\ExternalCalendar;

use App\Models\User;

interface CalendarEventIntegrationsProvider
{
    /**
     * @return array<int, array{
     *     integration_id: mixed,
     *      user_id: mixed,
     *      user_name: string,
     *      provider: string,
     *      provider_label: string,
     *      external_event: array{id: mixed, sync_status: string|null,
     *      logs: array<int, array{id: mixed, type: string, message: string, created_at: string|null}>}|null}>
     */
    public function forCalendarEvent(int $calendarEventId, User $user): array;
}
