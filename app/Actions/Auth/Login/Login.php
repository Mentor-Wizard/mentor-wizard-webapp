<?php

declare(strict_types=1);

namespace App\Actions\Auth\Login;

use App\Http\Requests\Auth\Login\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Lorisleiva\Actions\Concerns\AsController;

class Login
{
    use AsController;

    public function handle(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        return redirect()->intended(route('pages.dashboard'));
    }
}
