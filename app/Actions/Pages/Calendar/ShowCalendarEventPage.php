<?php

declare(strict_types=1);

namespace App\Actions\Pages\Calendar;

use App\Enums\RoleEnum;
use App\Http\Resources\EventShowResource;
use App\Models\Event;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;
use Lorisleiva\Actions\Concerns\AsController;

class ShowCalendarEventPage
{
    use AsController;

    public function handle(string $uuid): Response
    {
        return Inertia::render('Calendar/ShowEditEvent', [
            'canLogin'          => Route::has('login'),
            'canRegister'       => Route::has('register'),
            'laravelVersion'    => Application::VERSION,
            'phpVersion'        => PHP_VERSION,
            'locale'            => app()->getLocale(),
            'permissions'       => auth()->user()->hasRole(RoleEnum::MENTOR->value) ? 'edit' : 'view',
            'event'             => EventShowResource::collection(Event::query()->where('unique_id', $uuid)->get())->resolve(),
        ]);
    }
}
