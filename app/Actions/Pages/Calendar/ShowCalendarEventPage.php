<?php

declare(strict_types=1);

namespace App\Actions\Pages\Calendar;

use App\Enums\CalendarEventColoursEnum;
use App\Enums\RoleEnum;
use App\Http\Resources\EventShowResource;
use App\Models\CalendarEvent;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;
use Lorisleiva\Actions\Concerns\AsController;

class ShowCalendarEventPage
{
    use AsController;

    public function handle(CalendarEvent $calendarEvent, Request $request): Response
    {
        Gate::authorize('view', [$calendarEvent, auth()->user()]);

        $timezone = $request->query('timezone');

        return Inertia::render('Calendar/ShowEditEvent', [
            'canLogin'         => Route::has('login'),
            'canRegister'      => Route::has('register'),
            'laravelVersion'   => Application::VERSION,
            'phpVersion'       => PHP_VERSION,
            'locale'           => app()->getLocale(),
            'availableColours' => CalendarEventColoursEnum::values(),
            'permissions'      => auth()->user()->can('update',[$calendarEvent,auth()->user()]) ? 'edit' : 'view',
            'calendarEvent'    => new EventShowResource($calendarEvent->load('calendarEventUsers'))
                ->additional(['user' => auth()->user(),
                    'timezone'       => $timezone,
                ])
                ->resolve(),
        ]);
    }
}
