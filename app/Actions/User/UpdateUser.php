<?php

declare(strict_types=1);

namespace app\Actions\User;

use App\Http\Requests\Profile\UpdateUserRequest;
use Illuminate\Http\RedirectResponse;
use Lorisleiva\Actions\Concerns\AsController;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;

class UpdateUser
{
    use AsController;

    /**
     * @throws FileIsTooBig
     * @throws FileDoesNotExist
     */
    public function handle(UpdateUserRequest $request): RedirectResponse
    {
        $user = auth()->user();

        $user->email = $request->get('email');
        $user->username = $request->get('username');

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        if ($request->hasFile('avatar')) {

            $user->profile->clearMediaCollection('avatar');

            $user
                ->profile
                ->addMedia($request->file('avatar'))
                ->toMediaCollection('avatar');
        }

        return redirect()->route('profile.edit');
    }
}
