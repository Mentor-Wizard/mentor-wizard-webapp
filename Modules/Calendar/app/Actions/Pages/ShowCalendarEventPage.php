<?php

declare(strict_types=1);

namespace Modules\Calendar\Actions\Pages;

use App\Enums\CalendarSyncStatusEnum;
use App\Models\ExternalCalendarEvent;
use App\Models\ExternalCalendarEventLog;
use App\Models\User;
use App\Models\UserCalendarIntegration;
use App\Services\Calendar\AvailableSlotOptionsForMentorProgram;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Lorisleiva\Actions\Concerns\AsController;
use Modules\Calendar\DTO\CalendarUIEventData;
use Modules\Calendar\Enums\CalendarEventColoursEnum;
use Modules\Calendar\Models\CalendarEvent;

class ShowCalendarEventPage
{
    use AsController;

    public function handle(Request $request, CalendarEvent $calendarEvent): Response
    {
        /** @var User $user */
        $user = $request->user()->load('profile');
        $profile = $user->profile;
        $timezone = $profile->timezone;

        /** @var CalendarEvent $calendarEvent */
        return Inertia::render('Calendar/ShowEditCalendarEvent', [
            'locale'                => app()->getLocale(),
            'availableColours'      => CalendarEventColoursEnum::values(),
            'permissions'           => $user->can('update', $calendarEvent) ? 'edit' : 'view',
            'mentorProgramDuration' => $calendarEvent->mentorProgram->first()->session_duration,
            'availableSlots'        => new AvailableSlotOptionsForMentorProgram($calendarEvent)->getAvailableSlots(),
            'calendarEvent'         => CalendarUIEventData::fromModel(
                $calendarEvent->load('calendarEventUsers'),
                $timezone,
                $user
            )->toArray(),
            'externalIntegrations'  => Inertia::defer(fn (): array => $this->loadExternalIntegrations($calendarEvent, $user)),
        ]);
    }

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
    private function loadExternalIntegrations(CalendarEvent $calendarEvent, User $user): array
    {
        $integrations = UserCalendarIntegration::query()
            ->where('user_id', $user->getKey())
            ->where('sync_status', CalendarSyncStatusEnum::ACTIVE)
            ->get();

        $externalEvents = ExternalCalendarEvent::query()
            ->where('calendar_event_id', $calendarEvent->getKey())
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
