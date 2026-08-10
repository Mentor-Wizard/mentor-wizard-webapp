<?php

declare(strict_types=1);

namespace Modules\MentorSession\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Calendar\Enums\CalendarEventRoleEnum;
use Modules\Calendar\Enums\CalendarEventStatusEnum;
use Modules\Calendar\Models\CalendarEvent;
use Modules\MentorSession\Models\MentorSession;

class CreateMentorSessionForCalendarEvent
{
    use AsAction;

    public function handle(CalendarEvent $event): void
    {
        $statusValue = $event->status->value;

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
