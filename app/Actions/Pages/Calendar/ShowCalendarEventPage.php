<?php

declare(strict_types=1);

namespace App\Actions\Pages\Calendar;

use App\DTO\Calendar\CalendarUIEventData;
use App\Enums\CalendarEventColoursEnum;
use App\Enums\CalendarSyncStatusEnum;
use App\Models\CalendarEvent;
use App\Models\ExternalCalendarEvent;
use App\Models\ExternalCalendarEventLog;
use App\Models\UserCalendarIntegration;
use App\Services\Calendar\AvailableSlotOptionsForMentorProgram;
use Inertia\Inertia;
use Inertia\Response;
use Lorisleiva\Actions\Concerns\AsController;

class ShowCalendarEventPage
{
    use AsController;

    public function handle(CalendarEvent $calendarEvent): Response
    {
        $user = auth()->user();
        $profile = $user->profile;
        $timezone = $profile->timezone;

        $syncedProviders = ExternalCalendarEvent::query()
            ->where('calendar_event_id', $calendarEvent->getKey())
            ->where('user_id', $user->getKey())
            ->pluck('provider')
            ->map(fn ($p) => $p->value)
            ->all();

        $unsyncedProviders = UserCalendarIntegration::query()
            ->where('user_id', $user->getKey())
            ->where('sync_status', CalendarSyncStatusEnum::Active)
            ->whereNotIn('provider', $syncedProviders)
            ->get()
            ->map(fn (UserCalendarIntegration $integration): array => [
                'key'   => $integration->provider->value,
                'label' => $this->providerLabel($integration->provider->value),
            ])
            ->values()
            ->all();

        /** @var CalendarEvent $calendarEvent */
        return Inertia::render('Calendar/ShowEditCalendarEvent', [
            'locale'                => app()->getLocale(),
            'availableColours'      => CalendarEventColoursEnum::values(),
            'permissions'           => $user->can('update', $calendarEvent) ? 'edit' : 'view',
            'mentorProgramDuration' => $calendarEvent->mentorProgram->first()->session_duration,
            'availableSlots'        => new AvailableSlotOptionsForMentorProgram($calendarEvent)->getAvailableSlots(),
            'unsyncedProviders'     => $unsyncedProviders,
            'calendarEvent'         => CalendarUIEventData::fromModel(
                $calendarEvent->load('calendarEventUsers'),
                $timezone,
                $user
            )->toArray(),
            'externalIntegrations'  => Inertia::lazy(fn (): array => $this->loadExternalIntegrations($calendarEvent)),
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function loadExternalIntegrations(CalendarEvent $calendarEvent): array
    {
        $userIds = $calendarEvent->calendarEventUsers()->pluck('users.id');

        $integrations = UserCalendarIntegration::query()
            ->whereIn('user_id', $userIds)
            ->where('sync_status', CalendarSyncStatusEnum::Active)
            ->with('user:id,username')
            ->get();

        $externalEvents = ExternalCalendarEvent::query()
            ->where('calendar_event_id', $calendarEvent->getKey())
            ->with(['logs' => fn ($q) => $q->latest()->limit(20)])
            ->get()
            ->keyBy(fn (ExternalCalendarEvent $e): string => $e->user_id.'_'.$e->provider->value);

        return $integrations
            ->map(function (UserCalendarIntegration $integration) use ($externalEvents): array {
                $key = $integration->user_id.'_'.$integration->provider->value;
                $externalEvent = $externalEvents->get($key);

                return [
                    'integration_id' => $integration->getKey(),
                    'user_id'        => $integration->user_id,
                    'user_name'      => $integration->user->username,
                    'provider'       => $integration->provider->value,
                    'provider_label' => $this->providerLabel($integration->provider->value),
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

    private function providerLabel(string $provider): string
    {
        return match ($provider) {
            'google'              => 'Google Calendar',
            'google_personal_app' => 'Google Calendar (personal)',
            'outlook'             => 'Outlook Calendar',
            'apple'               => 'Apple Calendar',
            default               => ucfirst($provider),
        };
    }
}
