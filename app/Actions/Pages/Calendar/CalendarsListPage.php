<?php

declare(strict_types=1);

namespace App\Actions\Pages\Calendar;

use App\Enums\CalendarEventColoursEnum;
use App\Enums\CalendarViewModeEnum;
use App\Models\CalendarEvent;
use App\Services\Calendar\GetDailyCalendarEventsService;
use App\Services\Calendar\GetMonthCalendarEventsService;
use App\Services\Calendar\GetWeeklyCalendarEventsService;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;
use Lorisleiva\Actions\Concerns\AsController;

class CalendarsListPage
{
    use AsController;

    public function handle(Request $request): Response
    {
        $user = auth()->user();
        $timezone = $user?->profile->timezone ?? $request->get('timezone') ?? config('app.timezone');
        $date = $request->get('date') ? Date::parse($request->get('date'), $timezone) : Date::now($timezone);
        $mode = $request->get('mode') ? CalendarViewModeEnum::tryFrom($request->get('mode')) : CalendarViewModeEnum::MONTH;

        return Inertia::render('Calendar/CalendarsList', [
            'locale'            => app()->getLocale(),
            'permissions'       => $user?->can('create', CalendarEvent::class) ? 'create' : 'view',
            'availableColours'  => CalendarEventColoursEnum::values(),
            'calendarEvents'    => match ($mode) {
                CalendarViewModeEnum::DAY   => $timezone && $user
                    ? new DailyCalendarEventsService($user, $date, $timezone)->getDailyCalendarEvents() : [],
                CalendarViewModeEnum::WEEK  => $timezone && $user
                    ? new WeeklyCalendarEventsService($user, $date, $timezone)->getWeeklyCalendarEvents() : [],
                CalendarViewModeEnum::MONTH => $timezone && $user
                    ? new MonthCalendarEventsService($user, $date, $timezone)->getMonthCalendarEvents() : [],
            }]);
    }
}
