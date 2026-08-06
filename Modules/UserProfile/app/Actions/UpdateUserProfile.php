<?php

declare(strict_types=1);

namespace Modules\UserProfile\Actions;

use Illuminate\Http\RedirectResponse;
use Lorisleiva\Actions\Concerns\AsController;
use Modules\UserProfile\Http\Requests\UpdateUserProfileRequest;

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
