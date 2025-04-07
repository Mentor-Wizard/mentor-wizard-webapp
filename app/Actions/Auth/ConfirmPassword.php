<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Http\Requests\Auth\ConfirmPasswordRequest;
use Illuminate\Http\RedirectResponse;
use Lorisleiva\Actions\Concerns\AsController;

class ConfirmPassword
{
    use AsController;

    public function handle(ConfirmPasswordRequest $request): RedirectResponse
    {
        $request->session()->put('auth.password_confirmed_at', time());

        return redirect()->intended(route('pages.dashboard'));
    }
}
