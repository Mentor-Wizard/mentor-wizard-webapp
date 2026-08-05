<?php

declare(strict_types=1);

namespace Modules\ExternalCalendar\Support;

use App\Contracts\ExternalCalendar\CalendarEventIntegrationsProvider;
use App\Models\User;
use Modules\ExternalCalendar\Enums\CalendarSyncStatusEnum;
use Modules\ExternalCalendar\Models\ExternalCalendarEvent;
use Modules\ExternalCalendar\Models\ExternalCalendarEventLog;
use Modules\ExternalCalendar\Models\UserCalendarIntegration;

final class EloquentCalendarEventIntegrationsProvider implements CalendarEventIntegrationsProvider
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
    public function forCalendarEvent(int $calendarEventId, User $user): array
    {
        $integrations = UserCalendarIntegration::query()
            ->where('user_id', $user->getKey())
            ->where('sync_status', CalendarSyncStatusEnum::ACTIVE)
            ->get();

        $externalEvents = ExternalCalendarEvent::query()
            ->where('calendar_event_id', $calendarEventId)
            ->where('user_id', $user->getKey())
            ->with(['logs' => fn ($q) => $q->latest()->limit(20)])
            ->get()
            ->keyBy(fn (ExternalCalendarEvent $e): string => $e->provider->value);

        return $integrations
            ->map(function (UserCalendarIntegration $integration) use ($externalEvents, $user): array {
                $externalEvent = $externalEvents->get($integration->provider->value);

                return [
                    'integration_id' => $integration->getKey(),
                    'user_id'        => $user->getKey(),
                    'user_name'      => $user->username,
                    'provider'       => $integration->provider->value,
                    'provider_label' => $integration->provider->label(),
                    'external_event' => $externalEvent ? [
                        'id'          => $externalEvent->getKey(),
                        'sync_status' => $externalEvent->sync_status?->value,
                        'logs'        => $externalEvent->logs->map(fn (ExternalCalendarEventLog $log): array => [
                            'id'         => $log->getKey(),
                            'type'       => $log->type->value,
                            'message'    => $log->message,
                            'created_at' => $log->created_at?->toIso8601String(),
                        ])->all(),
                    ] : null,
                ];
            })
            ->values()
            ->all();
    }
}
