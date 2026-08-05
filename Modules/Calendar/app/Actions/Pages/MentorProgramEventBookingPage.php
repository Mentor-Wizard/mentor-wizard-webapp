<?php

declare(strict_types=1);

namespace Modules\Calendar\Actions\Pages;

use App\Models\MentorProgram;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Inertia\Inertia;
use Inertia\Response;
use Lorisleiva\Actions\Concerns\AsController;
use Modules\Calendar\Models\CalendarEvent;
use Modules\Calendar\Services\BookingCalendarEventsService;

class MentorProgramEventBookingPage
{
    use AsController;

    public function handle(Request $request, MentorProgram $mentorProgram): Response
    {
        $user = $request->user();
        $timezone = $user->profile->timezone;
        $date = $request->get('date') ? Date::parse($request->get('date'), $timezone) : Date::now($timezone);

        $calendarData = new BookingCalendarEventsService(
            $date,
            $user,
            $timezone,
            true,
            $mentorProgram
        )->getFormattedMonthAvailableSlots();

        $weekDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

        return Inertia::render('Calendar/MentorProgramEventBookingPage', [
            'locale'                => app()->getLocale(),
            'days'                  => $calendarData,
            'weekDays'              => $weekDays,
            'mentorProgram'         => $mentorProgram,
            'mentorSlug'            => $mentorProgram->mentor->slug,
            'roundingMinutes'       => CalendarEvent::ROUNDING_DISCRECY_TIME_IN_MINUTES,
            'currentDate'           => $date->toDateString(),
            'isMentorProgramOwner'  => $user->can('update', $mentorProgram),
        ]);
    }
}
