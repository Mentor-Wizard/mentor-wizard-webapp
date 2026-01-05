<?php

declare(strict_types=1);

namespace App\Actions\Calendar;

use App\Enums\CalendarEventRoleEnum;
use App\Http\Requests\Calendar\StoreCalendarEventRequest;
use App\Models\CalendarEvent;
use App\Models\MentorProgram;
use Illuminate\Http\RedirectResponse;

class StoreCalendarEvent extends BaseCalendarEventAction
{
    public function handle(StoreCalendarEventRequest $request): RedirectResponse
    {
        $validatedData = $this->getCalendarEventData($request);

        $colour = $validatedData['colour'];
        unset($validatedData['colour']);
        $calendarEvent = CalendarEvent::query()->create([
            ...$validatedData,
        ]);


        // If this is a mentor program booking, attach the mentor as a participant
        $mentorProgramId = $request->input('mentor_program_id');
            $mentorProgram = MentorProgram::query()->find($mentorProgramId);
            if ($mentorProgram && $mentorProgram->mentor_id) {
                    $calendarEvent->calendarEventUsers()->attach($mentorProgram->mentor_id, [
                        'role'   => CalendarEventRoleEnum::HOST->value,
                        'colour' => $colour,
                    ]);
            }

            if($mentorProgram->mentor_id !== auth()->user()->getKey()){
                $calendarEvent->calendarEventUsers()->attach(auth()->user()->getKey(), [
                    'role'   => CalendarEventRoleEnum::MENTI->value,
                    'colour' => $colour,
                ]);
            }

        return to_route('pages.calendar.index');
    }
}
