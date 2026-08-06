<?php

declare(strict_types=1);

namespace Modules\Auth\Actions;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Date;
use Lorisleiva\Actions\Concerns\AsController;
use Modules\Auth\Http\Requests\ConfirmPasswordRequest;

class ConfirmPassword
{
    use AsController;

    public function handle(ConfirmPasswordRequest $request): RedirectResponse
    {
        $request->session()->put('auth.password_confirmed_at', Date::now()->getTimestamp());

        return redirect()->intended(route('pages.dashboard'));
    }
}
