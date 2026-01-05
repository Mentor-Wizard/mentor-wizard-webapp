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

        if ($event->status === CalendarEventStatusEnum::CONFIRMED->value && $event->mentor_program_id) {
            $mentorSession = MentorSession::query()->create([
                'mentor_id' => $event->calendarEventUsers->query()
                    ->where('role', CalendarEventRoleEnum::HOST)->first()->user_id,

            ]);

        }
    }
}
