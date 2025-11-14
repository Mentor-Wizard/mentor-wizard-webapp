<?php

declare(strict_types=1);

namespace App\Actions\Calendar;

use App\Enums\CalendarEventRoleEnum;
use App\Http\Requests\Calendar\StoreCalendarEventRequest;
use App\Models\CalendarEvent;
use Illuminate\Http\RedirectResponse;
use Lorisleiva\Actions\Concerns\AsController;
use Symfony\Component\HttpFoundation\Response;

class StoreCalendarEvent
{
    use AsController;

    public function handle(StoreCalendarEventRequest $request): RedirectResponse
    {

        abort_if($request->user()->cannot('create', CalendarEvent::class), Response::HTTP_FORBIDDEN, 'Unauthorized action.');

        $validatedData = $request->getEventData();
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
