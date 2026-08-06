<?php

declare(strict_types=1);

namespace Modules\Auth\Actions\Register;

use Inertia\Inertia;
use Inertia\Response;
use Lorisleiva\Actions\Concerns\AsController;

class GetRegistrationPage
{
    use AsController;

    public function handle(): Response
    {
        return Inertia::render('Auth/RegisterPage');
    }
}
