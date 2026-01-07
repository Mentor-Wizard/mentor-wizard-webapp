<?php

declare(strict_types=1);

namespace App\Actions\Calendar;

use App\Enums\CalendarEventRoleEnum;
use App\Enums\CalendarEventStatusEnum;
use App\Models\CalendarEvent;
use Symfony\Component\HttpFoundation\Response;

class ConfirmCalendarEvent extends BaseCalendarEventAction
{
    public function handle(CalendarEvent $calendarEvent): Response
    {
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
            ->wherePivot('confirmed_at', '=')
            ->count() === 0) {
            $calendarEvent->update(['status' => CalendarEventStatusEnum::CONFIRMED->value]);

            return to_route('pages.calendar.pending')
                ->with('success', 'Event was successfully confirmed.');
        }

        return to_route('pages.calendar.pending')
            ->with('success', 'Event is confirmed on your side, but waiting for confirmation from CO-HOST');

    }
}
