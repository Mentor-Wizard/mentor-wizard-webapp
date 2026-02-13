<?php

declare(strict_types=1);

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
        $this->mentorProgram = MentorProgram::query()->find($calendarEvent->mentor_program_id);
        $this->mentor = $this->mentorProgram->mentor;
        $this->mentorSessionDuration = $this->mentorProgram->session_duration;

    }

    public function getAvailableSlots(): SplitSlotsPerSessionDuration
    {
        $availableSlots = new AvailableCalendarEventsSlotsService($this->mentor,
            $this->mentor->profile->timezone,
            $this->mentorProgram,
            [],
            true
        )->getAvailableSlots();

        return new SplitSlotsPerSessionDuration($availableSlots,
            $this->mentorSessionDuration,
            $this->mentor->profile->timezone);
    }
}
