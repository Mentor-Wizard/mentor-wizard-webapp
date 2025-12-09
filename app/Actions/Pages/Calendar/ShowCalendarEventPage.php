<?php

declare(strict_types=1);

namespace App\Actions\Pages\Calendar;

use App\Enums\CalendarEventColoursEnum;
use App\Http\Resources\Calendar\ShowCalendarEventResource;
use App\Models\CalendarEvent;
use Inertia\Inertia;
use Inertia\Response;
use Lorisleiva\Actions\Concerns\AsController;

class ShowCalendarEventPage
{
    use AsController;

    public function handle(CalendarEvent $calendarEvent): Response
    {
        return Inertia::render('Calendar/ShowEditEvent', [
            'availableColours' => CalendarEventColoursEnum::values(),
            'permissions'      => auth()->user()->can('update', [$calendarEvent, auth()->user()]) ? 'edit' : 'view',
            'calendarEvent'    => ShowCalendarEventResource::make($calendarEvent->load('calendarEventUsers')),
        ]);
    }
}
