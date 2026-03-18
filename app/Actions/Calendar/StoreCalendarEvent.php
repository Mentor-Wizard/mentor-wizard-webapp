<?php

declare(strict_types=1);

namespace App\Actions\Calendar;

use App\Enums\CalendarEventRoleEnum;
use App\Enums\CalendarEventStatusEnum;
use App\Http\Requests\Calendar\StoreCalendarEventRequest;
use App\Models\CalendarEvent;
use App\Models\MentorProgram;
use App\Models\MentorSession;
use Illuminate\Http\RedirectResponse;

class StoreCalendarEvent extends BaseCalendarEventAction
{
    public function handle(StoreCalendarEventRequest $request): RedirectResponse
    {
        $validatedData = $this->getCalendarEventData($request);

        $colour = $validatedData['colour'];
        $isConfirmed = $validatedData['status'] === CalendarEventStatusEnum::CONFIRMED;
        unset($validatedData['colour']);
        $calendarEvent = CalendarEvent::query()->create([
            ...$validatedData,
        ]);

        // If this is a mentor program booking, attach the mentor as a participant
        $mentorProgramId = $validatedData['mentor_program_id'];
        /** @var MentorProgram $mentorProgram */
        $mentorProgram = MentorProgram::query()->findOrFail($mentorProgramId);
        $calendarEvent->calendarEventUsers()->attach($mentorProgram->mentor_id, [
            'role'   => CalendarEventRoleEnum::HOST->value,
            'colour' => $colour,
        ]);

        if ($mentorProgram->mentor_id !== auth()->user()->getKey()) {
            $calendarEvent->calendarEventUsers()->attach(auth()->user()->getKey(), [
                'role'   => CalendarEventRoleEnum::PARTICIPANT->value,
                'colour' => $colour,
            ]);
        }

        if ($isConfirmed) {
            $participant = $calendarEvent->calendarEventUsers()
                ->wherePivot('role', CalendarEventRoleEnum::PARTICIPANT->value)
                ->first();

            if ($participant !== null) {
                $mentorSession = MentorSession::query()->create([
                    'mentor_id'         => $mentorProgram->mentor_id,
                    'menti_id'          => $participant->getKey(),
                    'date'              => $calendarEvent->start_date_time,
                    'cost'              => $mentorProgram->cost,
                    'mentor_program_id' => $calendarEvent->mentor_program_id,
                ]);
                $calendarEvent->update(['mentor_session_id' => $mentorSession->getKey()]);
            }
        }

        return to_route('pages.calendar.index');
    }
}
