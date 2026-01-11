<?php

declare(strict_types=1);

namespace App\Actions\Calendar;

use App\Enums\CalendarEventStatusEnum;
use App\Models\CalendarEvent;
use Illuminate\Http\RedirectResponse;
use Lorisleiva\Actions\Concerns\AsController;

class DeleteCalendarEvent
{
    use AsController;

    public function handle(CalendarEvent $calendarEvent): RedirectResponse
    {
        /** @phpstan-ignore-next-line instanceof.alwaysFalse. enum casting is for feature tests purposes */
        switch ($calendarEvent->status instanceof CalendarEventStatusEnum
            ? $calendarEvent->status->value : $calendarEvent->status) {
            case CalendarEventStatusEnum::PENDING_MENTOR_CONFIRMATION->value:
            case CalendarEventStatusEnum::PENDING_PAYMENT->value:
            case CalendarEventStatusEnum::CANCELLED->value:
                $calendarEvent->delete();
                break;

            case CalendarEventStatusEnum::CONFIRMED->value:
                $calendarEvent->update(['status' => CalendarEventStatusEnum::CANCELLED]);

                return to_route('pages.calendar.index')
                    ->with('error', 'Confirmed event cannot be deleted.');

            case CalendarEventStatusEnum::FINISHED->value:
                return to_route('pages.calendar.index')
                    ->with('error', 'Completed event cannot be deleted.');

            default:
                return to_route('pages.calendar.index')
                    ->with('error', 'Event cannot be deleted.');
        }

        return to_route('pages.calendar.index')
            ->with('success', 'Event was successfully deleted.');
    }
}
