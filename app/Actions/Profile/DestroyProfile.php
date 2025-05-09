<?php

declare(strict_types=1);

namespace App\Actions\Profile;

use App\Http\Requests\Profile\DestroyProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Lorisleiva\Actions\Concerns\AsController;

class DestroyProfile
{
    use AsController;

    public function handle(DestroyProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $user->delete();

        return redirect()->route('login');
    }
}
