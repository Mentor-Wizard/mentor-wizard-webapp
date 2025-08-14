<?php

declare(strict_types=1);

namespace App\Actions\Pages\Calendar;

use App\Enums\RoleEnum;
use App\Http\Resources\EventShowResource;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Application;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
use Inertia\Response;
use Inertia\Inertia;
use Lorisleiva\Actions\Concerns\AsController;
use Request;

class ShowCalendarEventPage
{
    use AsController;

    public function handle(String $uuid):Response
    {
        return Inertia::render('Calendar/ShowEditEvent', [
            'canLogin'       => Route::has('login'),
            'canRegister'    => Route::has('register'),
            'laravelVersion' => Application::VERSION,
            'phpVersion'     => PHP_VERSION,
            'locale'         => app()->getLocale(),
            'permissions'    => auth()->user()->hasRole(RoleEnum::MENTOR->value) ? 'edit' : 'view',
            'event'             => EventShowResource::collection(\App\Models\Event::query()->where('unique_id', $uuid)->get())->resolve(),
            ]);
    }
}
