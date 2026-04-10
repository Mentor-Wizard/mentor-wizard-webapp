<?php

declare(strict_types=1);

namespace App\Actions\Pages\Calendar;

use App\DTO\Calendar\CalendarEventData;
use App\Enums\CalendarEventColoursEnum;
use App\Enums\CalendarSyncStatusEnum;
use App\Models\CalendarEvent;
use App\Models\ExternalCalendarEvent;
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
            ->map(fn (UserCalendarIntegration $integration) => [
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
            'calendarEvent'         => CalendarEventData::fromModel(
                $calendarEvent->load('calendarEventUsers'),
                $timezone,
                $user
            )->toArray(),
        ]);
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
