<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Http\Requests\Auth\VerifyEmailRequest;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Lorisleiva\Actions\Concerns\AsController;

class VerifyEmail
{
    use AsController;

    public function handle(VerifyEmailRequest $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('pages.dashboard', ['verified' => 1]));
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }

        return redirect()->intended(route('pages.dashboard', ['verified' => 1]));
    }
}
