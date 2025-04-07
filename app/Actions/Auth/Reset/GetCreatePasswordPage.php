<?php

declare(strict_types=1);

namespace App\Actions\Auth\Reset;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Lorisleiva\Actions\Concerns\AsController;

class GetCreatePasswordPage
{
    use AsController;

    public function handle(Request $request): Response
    {
        return Inertia::render('Auth/ResetPassword', [
            'email' => $request->get('email'),
            'token' => $request->route('token'),
        ]);
    }
}
