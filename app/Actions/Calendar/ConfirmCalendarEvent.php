<?php

declare(strict_types=1);

namespace App\Actions\Calendar;

use App\Enums\CalendarEventRoleEnum;
use App\Enums\CalendarEventStatusEnum;
use App\Models\CalendarEvent;
use App\Models\MentorProgram;
use Date;
use Symfony\Component\HttpFoundation\Response;

class ConfirmCalendarEvent extends BaseCalendarEventAction
{
    public function handle(MentorProgram $mentorProgram, CalendarEvent $calendarEvent): Response
    {

        if ($calendarEvent->start_date_time->lessThan(Date::now())) {
            return to_route('pages.calendar.pending')
                ->with('error', 'Start time for this event is already past');
        }

        if ($mentorProgram->mentor->calendarEvents
            ->whereNotIn('id', [$calendarEvent->id])
            ->where('status', CalendarEventStatusEnum::CONFIRMED->value)
            ->where('start_date_time', '<', $calendarEvent->end_date_time)
            ->where('end_date_time', '>', $calendarEvent->start_date_time)
            ->count() > 0
        ) {

            return to_route('pages.calendar.pending')
                ->with('error', 'There are another confirmed event in this time slot.');
        }

        $calendarEvent->calendarEventUsers()
            ->wherePivot('user_id', auth()->id())
            ->updateExistingPivot(auth()->id(), [
                'confirmed_at' => now(),
            ]);

        if ($calendarEvent->calendarEventUsers()
            ->wherePivotIn('role', [
                CalendarEventRoleEnum::HOST->value,
                CalendarEventRoleEnum::COHOST->value,
            ])
            ->wherePivotNull('confirmed_at')
            ->count() === 0) {
            $calendarEvent->update(['status' => CalendarEventStatusEnum::CONFIRMED->value]);

            return to_route('pages.calendar.pending')
                ->with('success', 'Event was successfully confirmed.');
        }

        return to_route('pages.calendar.pending')
            ->with('success', 'Event is confirmed on your side, but waiting for confirmation from CO-HOST');

    }
}
