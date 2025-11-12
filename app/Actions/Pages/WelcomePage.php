<?php

declare(strict_types=1);

namespace App\Actions\Pages;

use App\Enums\RoleEnum;
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
        return Inertia::render('WelcomePage', [
            'canLogin'       => Route::has('login'),
            'canRegister'    => Route::has('register'),
            'laravelVersion' => Application::VERSION,
            'phpVersion'     => PHP_VERSION,
            'mentors'        => User::query()->role(RoleEnum::MENTOR->value)
                ->with(['profile'])
                ->paginate(User::DEFAULT_MENTOR_PAGE_PAGINATION),
        ]);
    }
}
