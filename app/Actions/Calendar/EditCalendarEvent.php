<?php

declare(strict_types=1);

namespace App\Actions\Calendar;

use App\Http\Requests\Calendar\EditCalendarEventRequest;
use App\Models\CalendarEvent;
use Symfony\Component\HttpFoundation\Response;

class EditCalendarEvent extends BaseCalendarEventAction
{
    public function handle(EditCalendarEventRequest $request, CalendarEvent $calendarEvent): Response
    {
        // Only update web_link according to the new business rule
        $validated = $request->validated();
        $calendarEvent->update([
            'web_link' => $validated['webLink'] ?? null,
        ]);

        return to_route('pages.calendar.index');
    }
}
