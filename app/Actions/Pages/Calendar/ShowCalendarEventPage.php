<?php

declare(strict_types=1);

namespace App\Actions\Pages\Calendar;

use App\DTO\Calendar\CalendarEventData;
use App\Enums\CalendarEventColoursEnum;
use App\Models\CalendarEvent;
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

        return Inertia::render('Calendar/ShowEditEvent', [
            'locale'           => app()->getLocale(),
            'availableColours' => CalendarEventColoursEnum::values(),
            'permissions'      => $user->can('update', $calendarEvent) ? 'edit' : 'view',
            'calendarEvent'    => CalendarEventData::fromModel(
                $calendarEvent->load('calendarEventUsers'),
                $timezone,
                $user
            )->toArray(),
        ]);
    }
}
