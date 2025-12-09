<?php

declare(strict_types=1);

namespace App\Actions\Calendar;

use App\Http\Requests\Calendar\EditCalendarEventRequest;
use App\Models\CalendarEvent;
use Arr;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Lorisleiva\Actions\Concerns\AsController;
use Symfony\Component\HttpFoundation\Response;

class EditCalendarEvent
{
    use AsController;

    public function handle(EditCalendarEventRequest $request, CalendarEvent $calendarEvent): Response
    {
        // FIXME: в мідлвери роутів
        throw_unless($calendarEvent->exists, ModelNotFoundException::class, 'Calendar Event not found.');

        $validatedData = $request->getEventData();
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
