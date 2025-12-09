<?php

declare(strict_types=1);

namespace App\Actions\Calendar;

use App\Enums\CalendarEventRoleEnum;
use App\Http\Requests\Calendar\StoreCalendarEventRequest;
use App\Models\CalendarEvent;
use Illuminate\Http\RedirectResponse;
use Lorisleiva\Actions\Concerns\AsController;

class StoreCalendarEvent extends BaseCalendarEventAction
{
    use AsController;

    public function handle(StoreCalendarEventRequest $request): RedirectResponse
    {
        $validatedData = $this->getCalendarEventData($request);

        $colour = $validatedData['colour'];
        unset($validatedData['colour']);
        $calendarEvent = CalendarEvent::query()->create([
            ...$validatedData,
        ]);

        $calendarEvent->calendarEventUsers()->attach(auth()->id(), [
            'role'   => CalendarEventRoleEnum::HOST->value,
            'colour' => $colour,
        ]);

        return to_route('pages.calendar.index');
    }
}
