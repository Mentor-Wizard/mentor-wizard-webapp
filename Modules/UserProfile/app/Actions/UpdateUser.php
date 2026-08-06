<?php

declare(strict_types=1);

namespace Modules\UserProfile\Actions;

use Illuminate\Http\RedirectResponse;
use Lorisleiva\Actions\Concerns\AsController;
use Modules\UserProfile\Http\Requests\UpdateUserRequest;
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
        $user = $request->user();

        $user->email = $request->get('email');
        $user->username = $request->get('username');

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        if ($request->hasFile('avatar')) {
            AddAvatar::run($user, $request->file('avatar'));
        }

        return to_route('profile.edit');
    }
}
