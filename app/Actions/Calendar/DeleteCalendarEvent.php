<?php

declare(strict_types=1);

namespace App\Actions\Calendar;

use App\Models\CalendarEvent;
use Illuminate\Http\RedirectResponse;
use Lorisleiva\Actions\Concerns\AsController;

class DeleteCalendarEvent
{
    use AsController;

    public function handle(CalendarEvent $calendarEvent): RedirectResponse
    {
        $calendarEvent->delete();

        return redirect()->route('pages.calendar.index');
    }
}
