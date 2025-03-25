<?php

declare(strict_types=1);

namespace App\Actions\Pages\Profile;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Lorisleiva\Actions\Concerns\AsController;

class GetProfilePage
{
    use AsController;

    public function handle(): Response
    {
        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => Auth::user() instanceof MustVerifyEmail, // @pest-mutate-ignore
            'status' => session('status'),
        ]);
    }
}
