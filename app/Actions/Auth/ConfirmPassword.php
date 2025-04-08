<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Http\Requests\Auth\ConfirmPasswordRequest;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsController;

class ConfirmPassword
{
    use AsController;

    public function handle(ConfirmPasswordRequest $request): RedirectResponse
    {
        $request->session()->put('auth.password_confirmed_at', Carbon::now()->getTimestamp());

        return redirect()->intended(route('pages.dashboard'));
    }
}
