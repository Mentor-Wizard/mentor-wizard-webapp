<?php

declare(strict_types=1);

namespace Modules\Auth\Actions\Login;

use Illuminate\Http\RedirectResponse;
use Lorisleiva\Actions\Concerns\AsController;
use Modules\Auth\Http\Requests\Login\LoginRequest;

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
