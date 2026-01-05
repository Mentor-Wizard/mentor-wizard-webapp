<?php

namespace App\Services\Calendar;

use App\Models\CalendarEvent;
use App\Models\MentorProgram;
use App\Models\User;

class AvailableSlotOptionsForMentorProgram
{

    protected MentorProgram $mentorProgram;
    protected int $mentorSessionDuration;
    protected User $mentor;
    public function __construct(CalendarEvent $calendarEvent)
    {
        $this->mentorProgram = $calendarEvent->mentorPrograms->first();
        $this->mentor = $this->mentorProgram->mentor;
        $this->mentorSessionDuration = $this->mentorProgram->session_duration;

    }

    public function getAvailableSlots()
    {
        $availableSlots  = new AvailableCalendarEventsSlotsService($this->mentor,
            $this->mentor->profile->timezone)->getAvailableSlots();
        return new SplitSlotsPerSessionDuration($availableSlots,
            $this->mentorSessionDuration,
            $this->mentor->profile->timezone);
    }
}
