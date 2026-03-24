<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\CalendarEventRoleEnum;
use App\Enums\CalendarEventStatusEnum;
use App\Models\CalendarEvent;
use App\Models\MentorSession;

class CalendarEventObserver
{
    public function updated(CalendarEvent $event): void
    {
        if (! $event->wasChanged('status')) {
            return;
        }

        $this->manageConfirmedCalendarEvent($event);
    }

    public function created(CalendarEvent $event): void
    {
        $this->manageConfirmedCalendarEvent($event);
    }

    private function manageConfirmedCalendarEvent(CalendarEvent $event): void
    {
        if ($event->status === CalendarEventStatusEnum::CONFIRMED->value && $event->mentor_program_id) {
            $hostUser = $event->calendarEventUsers()
                ->wherePivot('role', CalendarEventRoleEnum::HOST->value)
                ->first();

            $participant = $event->calendarEventUsers()
                ->wherePivot('role', CalendarEventRoleEnum::PARTICIPANT->value)
                ->first();

            if ($hostUser !== null && $participant !== null) {
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
    }
}
