<?php

declare(strict_types=1);

namespace Modules\Calendar\Actions\Pages;

use App\Contracts\ExternalCalendar\CalendarEventIntegrationsProvider;
use App\Models\User;
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

    public function __construct(
        private readonly CalendarEventIntegrationsProvider $externalIntegrationsProvider,
    ) {}

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
            'externalIntegrations'  => Inertia::defer(fn (): array => $this->externalIntegrationsProvider->forCalendarEvent(
                $calendarEvent->getKey(),
                $user
            )),
        ]);
    }
}
