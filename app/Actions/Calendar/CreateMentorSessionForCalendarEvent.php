<?php

declare(strict_types=1);

namespace App\Actions\Calendar;

use App\Enums\CalendarEventRoleEnum;
use App\Enums\CalendarEventStatusEnum;
use App\Models\CalendarEvent;
use App\Models\MentorSession;
use BackedEnum;

class CreateMentorSessionForCalendarEvent
{
    public function handle(CalendarEvent $event): void
    {
        // @phpstan-ignore-next-line
        $statusValue = $event->status instanceof BackedEnum
            ? $event->status->value
            : $event->status;

        if ($statusValue !== CalendarEventStatusEnum::CONFIRMED->value || ! $event->mentor_program_id) {
            return;
        }

        $hostUser = $event->calendarEventUsers()
            ->wherePivot('role', CalendarEventRoleEnum::HOST->value)
            ->first();

        $participant = $event->calendarEventUsers()
            ->wherePivot('role', CalendarEventRoleEnum::PARTICIPANT->value)
            ->first();

        if ($hostUser === null || $participant === null) {
            return;
        }

        $mentorSession = MentorSession::query()->create([
            'mentor_id'         => $hostUser->getKey(),
            'menti_id'          => $participant->getKey(),
            'date'              => $event->start_date_time,
            'cost'              => $event->mentorProgram->cost,
            'mentor_program_id' => $event->mentor_program_id,
        ]);

        $event->mentor_session_id = $mentorSession->getKey();
        $event->saveQuietly();
    }
}
