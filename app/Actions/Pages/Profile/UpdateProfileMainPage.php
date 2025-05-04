<?php

declare(strict_types=1);

namespace App\Actions\Pages\Profile;

use App\Http\Requests\Profile\UpdateProfileMainRequest;
use App\Models\UserProfile;
use Illuminate\Http\RedirectResponse;
use Lorisleiva\Actions\Concerns\AsController;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;

class UpdateProfileMainPage
{
    use AsController;

    /**
     * @throws FileIsTooBig
     * @throws FileDoesNotExist
     */
    public function handle(UpdateProfileMainRequest $request): RedirectResponse
    {
        $user = auth()->user();

        $user->email = $request->get('email');

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        /** @var UserProfile $profile */
        $profile = $user->profile()->updateOrCreate([], $this->getRequestData($request));

        if ($request->hasFile('avatar')) {

            $profile->clearMediaCollection('avatar');

            $profile
                ->addMedia($request->file('avatar'))
                ->toMediaCollection('avatar');
        }

        return redirect()->route('profile.edit');
    }

    private function getRequestData(UpdateProfileMainRequest $request): array
    {
        return [
            'name'        => $request->get('name'),
            'last_name'   => $request->get('last_name'),
        ];
    }
}
