<?php

declare(strict_types=1);

namespace App\Actions\Profile;

use App\Http\Requests\UserProfile\DeleteUserProfileRequest;
use Illuminate\Http\RedirectResponse;
use Lorisleiva\Actions\Concerns\AsController;

class DeleteUserProfile
{
    use AsController;

    public function handle(DeleteUserProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        auth()->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $user->delete();

        return to_route('login');
    }
}
