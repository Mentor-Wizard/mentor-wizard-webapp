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
        $timezone = $user?->profile?->timezone ?? config('app.timezone');

        return Inertia::render('Calendar/ShowEditEvent', [
            'availableColours' => CalendarEventColoursEnum::values(),
            'permissions'      => $user->can('update', [$calendarEvent, $user]) ? 'edit' : 'view',
            'calendarEvent'    => CalendarEventData::fromModel(
                $calendarEvent->load('calendarEventUsers'),
                $timezone,
                $user
            )->toArray(),
        ]);
    }
}
