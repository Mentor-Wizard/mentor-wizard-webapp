<?php

declare(strict_types=1);

namespace Modules\Auth\Actions;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Lorisleiva\Actions\Concerns\AsController;

class VerificationEmailPrompt
{
    use AsController;

    public function handle(Request $request): RedirectResponse|Response
    {
        return $request->user()->hasVerifiedEmail()
                    ? redirect()->intended(route('pages.dashboard'))
                    : Inertia::render('Auth/VerifyEmail', ['status' => session('status')]);
    }
}
