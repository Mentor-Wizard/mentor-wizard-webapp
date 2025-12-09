<?php

declare(strict_types=1);

namespace App\Actions\Calendar;

use App\Http\Requests\Calendar\EditCalendarEventRequest;
use App\Models\CalendarEvent;
use Arr;
use Symfony\Component\HttpFoundation\Response;

class EditCalendarEvent extends BaseCalendarEventAction
{
    public function handle(EditCalendarEventRequest $request, CalendarEvent $calendarEvent): Response
    {
        $validatedData = $this->getCalendarEventData($request);
        $colour = Arr::get($validatedData, 'colour');
        unset($validatedData['colour']);
        $calendarEvent->update([
            ...$validatedData,
        ]);

        $calendarEvent->calendarEventUsers()->syncWithPivotValues(auth()->id(), [
            'colour' => $colour,
        ], false);

        return to_route('pages.calendar.index');
    }
}
