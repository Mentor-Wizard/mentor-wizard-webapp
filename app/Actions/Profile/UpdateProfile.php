<?php

declare(strict_types=1);

namespace app\Actions\Profile;

use App\Http\Requests\Profile\UpdateProfileRequest;
use Illuminate\Http\RedirectResponse;
use Lorisleiva\Actions\Concerns\AsController;

class UpdateProfile
{
    use AsController;

    public function handle(UpdateProfileRequest $request): RedirectResponse
    {
        $user = auth()->user();

        $user->profile->update($request->validated());

        return redirect()->route('profile.edit');
    }
}
