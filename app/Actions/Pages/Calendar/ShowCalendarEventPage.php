<?php

declare(strict_types=1);

namespace App\Actions\Pages\Calendar;

use App\Enums\CalendarEventColoursEnum;
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
        // FIXME: винести в мідлвери роутів?
        Gate::authorize('view', [$calendarEvent, auth()->user()]);

        $timezone = $request->query('timezone');

        return Inertia::render('Calendar/ShowEditEvent', [
            'canLogin'         => Route::has('login'), // FIXME: чому це тут потрібно?!
            'canRegister'      => Route::has('register'), // FIXME: чому це тут потрібно?!
            'laravelVersion'   => Application::VERSION, // FIXME: чому це тут потрібно?!
            'phpVersion'       => PHP_VERSION, // FIXME: чому це тут потрібно?!
            'locale'           => app()->getLocale(), // FIXME: чому беремо це саме тут?!
            'availableColours' => CalendarEventColoursEnum::values(), // FIXME: colors?
            'permissions'      => auth()->user()->can('update', [$calendarEvent, auth()->user()]) ? 'edit' : 'view',
            // FIXME: make() буде краще сприйматись в екосистемі Ларки
            'calendarEvent'    => new EventShowResource($calendarEvent->load('calendarEventUsers'))
                ->additional(['user' => auth()->user(),
                    'timezone'       => $timezone,
                ])
                ->resolve(),
        ]);
    }
}
