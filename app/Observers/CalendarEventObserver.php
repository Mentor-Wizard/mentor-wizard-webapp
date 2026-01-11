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
            $hostUser = $event->calendarEventUsers()
                ->wherePivot('role', CalendarEventRoleEnum::HOST->value)
                ->first();

            if ($hostUser !== null) {
                MentorSession::query()->create([
                    'mentor_id' => (int) $hostUser->getKey(),
                ]);
            }
        }
    }
}
