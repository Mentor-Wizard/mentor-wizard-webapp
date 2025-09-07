<?php

declare(strict_types=1);

namespace App\Actions\Pages\Calendar;

use App\Actions\Calendar\Services\GetDailyEvents;
use App\Actions\Calendar\Services\GetMonthEvents;
use App\Actions\Calendar\Services\GetWeeklyEvents;
use App\Enums\RoleEnum;
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
        $data = $request->all();
        $timeZone = $data['timeZone'] ?? 'UTC';
        $date = $data['date'] ?? Carbon::now($timeZone)->format('Y-m-d');
        $mode = $data['mode'] ?? 'Month view';
        if (! auth()->user()) {
            return Inertia::render('Auth/Login',
                [
                    'canLogin'          => Route::has('login'),
                    'canRegister'       => Route::has('register'),
                    'laravelVersion'    => Application::VERSION,
                    'phpVersion'        => PHP_VERSION,
                    'locale'            => app()->getLocale(),
                ]
            );
        }

        $user = auth()->user();

        return Inertia::render('Calendar/CalendarsList', [
            'canLogin'          => Route::has('login'),
            'canRegister'       => Route::has('register'),
            'laravelVersion'    => Application::VERSION,
            'phpVersion'        => PHP_VERSION,
            'locale'            => app()->getLocale(),
            'permissions'       => ($user->hasRole(RoleEnum::MENTOR->value) === true) ? 'edit' : 'view',
            'events'            => match ($mode) {
                'Week view'     => new GetWeeklyEvents(auth()->user(), $date, $timeZone)->execute(),
                'Day view'      => new GetDailyEvents(auth()->user(), $date, $timeZone)->execute(),
                default         => new GetMonthEvents(auth()->user(), $date, $timeZone)->execute(),
            }]);
    }
}
