<?php

declare(strict_types=1);

namespace App\Actions\Profile;

use App\Http\Requests\UserProfile\DeleteUserProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Lorisleiva\Actions\Concerns\AsController;

class DeleteUserProfile
{
    use AsController;

    public function handle(DeleteUserProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $user->delete();

        return redirect()->route('login');
    }
}
