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

        // Attach the current user as HOST
        $calendarEvent->calendarEventUsers()->attach(auth()->id(), [
            'role'   => CalendarEventRoleEnum::HOST->value,
            'colour' => $colour,
        ]);

        // If this is a mentor program booking, attach the mentor as a participant
        $mentorProgramId = $request->input('mentor_program_id');
        if ($mentorProgramId) {
            $mentorProgram = MentorProgram::query()->find($mentorProgramId);
            if ($mentorProgram && $mentorProgram->mentor_profile_id) {
                $mentor = $mentorProgram->mentorProfile->user;
                if ($mentor && $mentor->getKey() !== auth()->id()) {
                    $calendarEvent->calendarEventUsers()->attach($mentor->getKey(), [
                        'role'   => CalendarEventRoleEnum::PARTICIPANT->value,
                        'colour' => $colour,
                    ]);
                }
            }
        }

        return to_route('pages.calendar.index');
    }
}
