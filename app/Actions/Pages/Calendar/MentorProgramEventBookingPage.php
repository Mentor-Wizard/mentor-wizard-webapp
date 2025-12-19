<?php

declare(strict_types=1);

namespace App\Actions\Pages\Calendar;

use App\Models\MentorProgram;
use App\Services\Calendar\GetBookingCalendarEventsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Inertia\Inertia;
use Inertia\Response;
use Lorisleiva\Actions\Concerns\AsController;

class MentorProgramEventBookingPage
{
    use AsController;

    public function handle(Request $request): Response
    {
        $timezone = $request->get('timezone') ?? config('app.timezone');
        $date = $request->get('date') ? Date::parse($request->get('date'), $timezone) : Date::now($timezone);
        $mentorProgramId = $request->get('mentorProgramId');
        $user = auth()->user();
        $mentorProgram = $mentorProgramId ? MentorProgram::query()->find($mentorProgramId) : null;

        $calendarData = new GetBookingCalendarEventsService(
            $date,
            $user,
            $timezone,
            true,
            $mentorProgram
        )->getFormattedMonthAvailableSlots();

        $weekDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

        return Inertia::render('Calendar/MentorProgramEventBookingPage', [
            'locale'            => app()->getLocale(),
            'days'              => $calendarData,
            'weekDays'          => $weekDays,
            'mentorProgram'     => $mentorProgram,
        ]);
    }
}
