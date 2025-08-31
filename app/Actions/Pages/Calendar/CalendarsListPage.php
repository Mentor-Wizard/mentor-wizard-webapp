<?php

declare(strict_types=1);

namespace App\Actions\Pages\Calendar;

use App\Enums\RoleEnum;
use App\Models\User;
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
        $timezone = $data['timezone'] ?? 'UTC';
        $date = $data['date'] ?? Carbon::now('UTC')->format('Y-m-d');
        $mode = $data['mode'] ?? 'Month view';

        return Inertia::render('Calendar/CalendarsList', [
            'canLogin'       => Route::has('login'),
            'canRegister'    => Route::has('register'),
            'laravelVersion' => Application::VERSION,
            'phpVersion'     => PHP_VERSION,
            'locale'         => app()->getLocale(),
            'permissions'    => auth()->user()->hasRole(RoleEnum::MENTOR->value) ? 'edit' : 'view',
            'events'         => match ($mode) {
                'Month view'    => User::query()->find(auth()->id())?->getMonthFormattedEvents($date, $timezone),
                'Week view'     => User::query()->find(auth()->id())?->getWeekFormattedEvents($date, $timezone),
                'Day view'      => User::query()->find(auth()->id())?->getDailyFormattedEvents($date, $timezone),
                default         => User::query()->find(auth()->id())?->getMonthFormattedEvents($date, $timezone)
            }]);
    }
}
