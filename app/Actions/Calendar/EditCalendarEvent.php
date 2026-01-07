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

        $calendarEvent->update([
            'web_link'    => $validated['webLink'] ?? null,
            'description' => $validated['description'] ?? null,
        ]);
        $colour = Arr::get($validated, 'colour');
        $calendarEvent->calendarEventUsers()
            ->wherePivot('user_id', auth()->id())
            ->syncWithPivotValues(auth()->id(), [
                'colour' => $colour,
            ], false);

        return to_route('pages.calendar.index');
    }
}
