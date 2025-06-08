<?php

declare(strict_types=1);

namespace App\Actions\Pages;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;
use Lorisleiva\Actions\Concerns\AsController;

class WelcomePage
{
    use AsController;

    public function handle(): Response
    {
        return Inertia::render('Welcome', [
            'canLogin'       => Route::has('login'),
            'canRegister'    => Route::has('register'),
            'laravelVersion' => Application::VERSION,
            'phpVersion'     => PHP_VERSION,
            'mentors'        => new UserResource(User::query()
                ->role('mentor')
                ->with(['profile', 'profile.media'])->paginate(5)),
        ]);
    }
}
