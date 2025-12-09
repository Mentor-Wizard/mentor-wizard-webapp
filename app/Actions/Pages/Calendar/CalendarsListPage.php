<?php

declare(strict_types=1);

namespace App\Actions\Pages\Calendar;

use App\Enums\CalendarEventColoursEnum;
use App\Enums\CalendarViewModeEnum;
use App\Models\CalendarEvent;
use App\Services\Calendar\DailyCalendarEventsService;
use App\Services\Calendar\MonthCalendarEventsService;
use App\Services\Calendar\WeeklyCalendarEventsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Inertia\Inertia;
use Inertia\Response;
use Lorisleiva\Actions\Concerns\AsController;

class CalendarsListPage
{
    use AsController;

    public function handle(Request $request): Response
    {
        $timezone = $request->get('timezone') ?? config('app.timezone');
        $date = $request->get('date') ? Date::parse($request->get('date'), $timezone) : Date::now($timezone);
        $mode = CalendarViewModeEnum::tryFrom($request->get('mode')) ?? CalendarViewModeEnum::MONTH;
        $user = auth()->user();

        return Inertia::render('Calendar/CalendarsList', [
            'locale'            => app()->getLocale(),
            'permissions'       => $user?->can('create', CalendarEvent::class) ? 'create' : 'view',
            'availableColours'  => CalendarEventColoursEnum::values(),
            'calendarEvents'    => match ($mode) {
                CalendarViewModeEnum::DAY   => $timezone && $user ? new DailyCalendarEventsService($user, $date, $timezone)->getDailyCalendarEvents() : [],
                CalendarViewModeEnum::WEEK  => $timezone && $user ? new WeeklyCalendarEventsService($user, $date, $timezone)->getWeeklyCalendarEvents() : [],
                CalendarViewModeEnum::MONTH => $timezone && $user ? new MonthCalendarEventsService($user, $date, $timezone)->getMonthCalendarEvents() : [],
            }]);
    }
}
