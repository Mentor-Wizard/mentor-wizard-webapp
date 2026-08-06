<?php

declare(strict_types=1);

namespace Modules\UserProfile\Actions;

use Illuminate\Http\RedirectResponse;
use Lorisleiva\Actions\Concerns\AsController;
use Modules\UserProfile\Http\Requests\DeleteUserProfileRequest;

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
