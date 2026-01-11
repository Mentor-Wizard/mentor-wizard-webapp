<?php

declare(strict_types=1);

namespace App\Actions\Pages\Calendar;

use App\DTO\Calendar\CalendarEventData;
use App\Enums\CalendarEventColoursEnum;
use App\Models\CalendarEvent;
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

        /** @var CalendarEvent $calendarEvent */
        return Inertia::render('Calendar/ShowEditCalendarEvent', [
            'locale'                => app()->getLocale(),
            'availableColours'      => CalendarEventColoursEnum::values(),
            'permissions'           => $user->can('update', [$calendarEvent, $user]) ? 'edit' : 'view',
            'mentorProgramDuration' => $calendarEvent->mentorProgram->first()->session_duration,
            'availableSlots'        => new AvailableSlotOptionsForMentorProgram($calendarEvent)->getAvailableSlots(),
            'calendarEvent'         => CalendarEventData::fromModel(
                $calendarEvent->load('calendarEventUsers'),
                $timezone,
                $user
            )->toArray(),
        ]);
    }
}
