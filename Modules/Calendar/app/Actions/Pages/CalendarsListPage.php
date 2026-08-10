<?php

declare(strict_types=1);

namespace Modules\Calendar\Actions\Pages;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Inertia\Inertia;
use Inertia\Response;
use Lorisleiva\Actions\Concerns\AsController;
use Modules\Calendar\Enums\CalendarEventColoursEnum;
use Modules\Calendar\Enums\CalendarViewModeEnum;
use Modules\Calendar\Services\DailyCalendarEventsService;
use Modules\Calendar\Services\MonthCalendarEventsService;
use Modules\Calendar\Services\WeeklyCalendarEventsService;

class CalendarsListPage
{
    use AsController;

    public function handle(Request $request): Response
    {
        $user = $request->user();
        $timezone = $user->profile->timezone;
        $date = $request->get('date')
            ? Date::parse($request->get('date'), $timezone)
            : Date::now($timezone);
        $mode = $request->get('mode') ? CalendarViewModeEnum::tryFrom($request->get('mode')) : CalendarViewModeEnum::MONTH;

        return Inertia::render('Calendar/CalendarEventsList', [
            'locale'            => app()->getLocale(),
            'permissions'       => 'create',
            'availableColours'  => CalendarEventColoursEnum::values(),
            'calendarEvents'    => match ($mode) {
                CalendarViewModeEnum::DAY   => new DailyCalendarEventsService($user, $date, $timezone)
                    ->getDailyCalendarEvents(),
                CalendarViewModeEnum::WEEK  => new WeeklyCalendarEventsService($user, $date, $timezone)
                    ->getWeeklyCalendarEvents() ,
                CalendarViewModeEnum::MONTH => new MonthCalendarEventsService($user, $date, $timezone)
                    ->getMonthCalendarEvents(),
                default                     => new MonthCalendarEventsService($user, $date, $timezone)
                    ->getMonthCalendarEvents(),
            }]);
    }
}
