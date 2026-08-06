<?php

declare(strict_types=1);

namespace Modules\Auth\Actions\Reset;

use Inertia\Inertia;
use Inertia\Response;
use Lorisleiva\Actions\Concerns\AsController;

class GetResetPasswordPage
{
    use AsController;

    public function handle(): Response
    {
        return Inertia::render('Auth/ForgotPassword', [
            'status' => session('status'),
        ]);
    }
}
