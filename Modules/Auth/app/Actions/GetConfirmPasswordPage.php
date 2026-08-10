<?php

declare(strict_types=1);

namespace Modules\Auth\Actions;

use Inertia\Inertia;
use Inertia\Response;
use Lorisleiva\Actions\Concerns\AsController;

class GetConfirmPasswordPage
{
    use AsController;

    public function handle(): Response
    {
        return Inertia::render('Auth/ConfirmPassword');
    }
}
