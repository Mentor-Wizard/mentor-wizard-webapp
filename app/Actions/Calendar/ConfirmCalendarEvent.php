<?php

declare(strict_types=1);

namespace App\Actions\Calendar;

use App\Enums\CalendarEventRoleEnum;
use App\Enums\CalendarEventStatusEnum;
use App\Models\CalendarEvent;
use App\Models\MentorProgram;
use App\Notifications\CalendarEventConfirmedNotification;
use Date;
use Lorisleiva\Actions\Concerns\AsController;
use Symfony\Component\HttpFoundation\Response;

class ConfirmCalendarEvent
{
    use AsController;

    public function handle(MentorProgram $mentorProgram, CalendarEvent $calendarEvent): Response
    {

        if ($calendarEvent->start_date_time->lessThan(Date::now())) {
            return to_route('pages.calendar.pending')
                ->with('error', 'Start time for this event is already past');
        }

        if ($mentorProgram->mentor->calendarEvents()
            ->whereNotIn('calendar_event_id', [$calendarEvent->id])
            ->where('status', CalendarEventStatusEnum::CONFIRMED->value)
            ->where('start_date_time', '<', $calendarEvent->end_date_time)
            ->where('end_date_time', '>', $calendarEvent->start_date_time)
            ->exists()
        ) {

            return to_route('pages.calendar.pending')
                ->with('error', 'There are another confirmed event in this time slot.');
        }

        $this->fillConfirmationDates($calendarEvent);

        if (! $calendarEvent->calendarEventUsers()
            ->wherePivotIn('role', [
                CalendarEventRoleEnum::HOST->value,
                CalendarEventRoleEnum::COHOST->value,
            ])
            ->wherePivotNull('confirmed_at')
            ->exists()) {

            $calendarEvent->update(['status' => CalendarEventStatusEnum::CONFIRMED->value]);
            $this->notifyUserAboutConfirmation($calendarEvent);
            $this->checkEventsForCancellation($mentorProgram, $calendarEvent);

            return to_route('pages.calendar.pending')
                ->with('success', 'Event was successfully confirmed.');
        }

        return to_route('pages.calendar.pending')
            ->with('success', 'Event is confirmed on your side, but waiting for confirmation from CO-HOST');

    }

    private function fillConfirmationDates(CalendarEvent $calendarEvent): void
    {
        $calendarEvent->calendarEventUsers()
            ->updateExistingPivot(auth()->id(), [
                'confirmed_at' => now(),
            ]);

    }

    private function notifyUserAboutConfirmation(CalendarEvent $calendarEvent): void
    {
        $calendarEvent->calendarEventUsers()
            ->wherePivot('role', CalendarEventRoleEnum::MENTI->value)
            ->get()
            ->each(fn ($user) => $user->notify(new CalendarEventConfirmedNotification($calendarEvent)));

    }

    private function checkEventsForCancellation(MentorProgram $mentorProgram, CalendarEvent $calendarEvent): void
    {
        $overlappingIds = $mentorProgram->mentor->calendarEvents()
            ->whereNotIn('calendar_event_id', [$calendarEvent->getKey()])
            ->where('status', CalendarEventStatusEnum::PENDING_MENTOR_CONFIRMATION->value)
            ->where('start_date_time', '<', $calendarEvent->end_date_time)
            ->where('end_date_time', '>', $calendarEvent->start_date_time)
            ->pluck('calendar_events.id');

        if ($overlappingIds->isNotEmpty()) {
            CalendarEvent::query()
                ->whereIn('id', $overlappingIds)
                ->update(['status' => CalendarEventStatusEnum::CANCELLED->value]);
        }
    }
}
