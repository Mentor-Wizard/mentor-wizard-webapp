<?php

declare(strict_types=1);

namespace App\Actions\Pages\Calendar;

use App\Enums\CalendarEventColoursEnum;
use App\Enums\RoleEnum;
use App\Services\Calendar\GetDailyEventsService;
use App\Services\Calendar\GetMonthEventsService;
use App\Services\Calendar\GetWeeklyEventsService;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;
use Lorisleiva\Actions\Concerns\AsController;

class CalendarsListPage
{
    use AsController;

    public function handle(Request $request): Response
    {
        $timezone = $request->get('timezone');
        $date = $request->get('date') ?? Carbon::now($timezone)->format('Y-m-d');
        $mode = $request->get('mode') ?? 'Month view';
        $user = auth()->user();

        return Inertia::render('Calendar/CalendarsList', [
            'canLogin'          => Route::has('login'),
            'canRegister'       => Route::has('register'),
            'laravelVersion'    => Application::VERSION,
            'phpVersion'        => PHP_VERSION,
            'locale'            => app()->getLocale(),
            'permissions'       => $user?->hasRole(RoleEnum::MENTOR->value) ? 'edit' : 'view',
            'availableColours'  => CalendarEventColoursEnum::values(),
            'events'            => match ($mode) {
                'Day view'      => $timezone && $user ? new GetDailyEventsService($user, $date, $timezone)->execute() : [],
                'Week view'     => $timezone && $user ? new GetWeeklyEventsService($user, $date, $timezone)->execute() : [],
                default         => $timezone && $user ? new GetMonthEventsService($user, $date, $timezone)->execute() : [],
            }]);
    }
}
