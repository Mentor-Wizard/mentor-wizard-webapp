<?php

declare(strict_types=1);

namespace App\Actions\Calendar;

use App\Http\Requests\Calendar\EditCalendarEventRequest;
use App\Models\CalendarEvent;
use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\Response;

class EditCalendarEvent extends BaseCalendarEventAction
{
    public function handle(EditCalendarEventRequest $request, CalendarEvent $calendarEvent): Response
    {
        // Only update web_link according to the new business rule
        $validated = $request->validated();

        $webLink = array_key_exists('webLink', $validated) ? $validated['webLink'] : $calendarEvent->web_link;
        $description = array_key_exists('description', $validated)
            ? $validated['description']
            : $calendarEvent->description;
        $calendarEvent->update([
            'web_link'    => $webLink,
            'description' => $description,
        ]);
        $colour = Arr::get($validated, 'colour');
        $calendarEvent->calendarEventUsers()
            ->syncWithoutDetaching([
                auth()->id() => ['colour' => $colour],
            ]);

        return to_route('pages.calendar.index');
    }
}
