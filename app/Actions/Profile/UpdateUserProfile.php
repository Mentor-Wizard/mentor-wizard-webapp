<?php

declare(strict_types=1);

namespace App\Actions\Profile;

use App\Http\Requests\Profile\UpdateProfileRequest;
use Illuminate\Http\RedirectResponse;
use Lorisleiva\Actions\Concerns\AsController;

class UpdateUserProfile
{
    use AsController;

    public function handle(UpdateProfileRequest $request): RedirectResponse
    {
        $user = auth()->user();

        $user->profile->update($request->validated());

        return redirect()->route('profile.edit');
    }
}
