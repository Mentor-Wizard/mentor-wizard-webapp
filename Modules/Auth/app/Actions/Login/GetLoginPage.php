<?php

declare(strict_types=1);

namespace Modules\Auth\Actions\Login;

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;
use Lorisleiva\Actions\Concerns\AsController;

class GetLoginPage
{
    use AsController;

    public function handle(): Response
    {
        return Inertia::render('Auth/LoginPage', [
            'canResetPassword' => Route::has('password.request'),
            'status'           => session('status'),
        ]);
    }
}
