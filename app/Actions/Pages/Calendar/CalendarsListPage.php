<?php

declare(strict_types=1);

namespace App\Actions\Pages\Calendar;

use App\Enums\CalendarEventColoursEnum;
use App\Enums\RoleEnum;
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
        $timezone = $request->get('timezone') ?? config('app.timezone');
        $date = $request->get('date') ? Date::parse($request->get('date'), $timezone) : Date::now($timezone);
        $mode = $request->get('mode') ?? 'Month view';
        $user = auth()->user();

        return Inertia::render('Calendar/CalendarsList', [
            'canLogin'          => Route::has('login'),
            'canRegister'       => Route::has('register'),
            'laravelVersion'    => Application::VERSION,
            'phpVersion'        => PHP_VERSION,
            'locale'            => app()->getLocale(),
            'permissions'       => $user?->can('create',CalendarEvent::class) ? 'create' : 'view',
            'availableColours'  => CalendarEventColoursEnum::values(),
            'calendarEvents'    => match ($mode) {
                'Day view'      => $timezone && $user ? new GetDailyCalendarEventsService($user, $date, $timezone)->getDailyCalendarEvents() : [],
                'Week view'     => $timezone && $user ? new GetWeeklyCalendarEventsService($user, $date, $timezone)->getWeeklyCalendarEvents() : [],
                default         => $timezone && $user ? new GetMonthCalendarEventsService($user, $date, $timezone)->getMonthCalendarEvents() : [],
            }]);
    }
}
