<?php

declare(strict_types=1);

namespace App\Actions\Profile;

use App\Http\Requests\UserProfile\UpdateUserProfileRequest;
use Illuminate\Http\RedirectResponse;
use Lorisleiva\Actions\Concerns\AsController;

class UpdateUserProfile
{
    use AsController;

    public function handle(UpdateUserProfileRequest $request): RedirectResponse
    {
        $user = auth()->user();

        $user->profile->update($request->validated());

        return to_route('profile.edit');
    }
}
