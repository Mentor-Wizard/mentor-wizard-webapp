<?php

declare(strict_types=1);

namespace App\Actions\Pages\Profile;

use App\Http\Requests\Profile\UpdateProfileRequest;
use Illuminate\Http\RedirectResponse;
use Lorisleiva\Actions\Concerns\AsController;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;

class UpdateProfilePage
{
    use AsController;

    /**
     * @throws FileIsTooBig
     * @throws FileDoesNotExist
     */
    public function handle(UpdateProfileRequest $request): RedirectResponse
    {
        $user = auth()->user();

        $user->email = $request->get('email');

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->email = $request->get('email');
        $user->save();
        $user->profile()->updateOrCreate([], $this->dataUpdate($request));

        if ($request->hasFile('avatar')) {

            $user
                ->profile
                ->clearMediaCollection('avatar');

            $user
                ->profile
                ->addMedia($request->file('avatar'))
                ->toMediaCollection('avatar');
        }

        return redirect()->route('profile.edit');
    }

    private function dataUpdate(UpdateProfileRequest $request): array
    {
        return [
            'name'      => $request->get('name'),
            'last_name' => $request->get('last_name'),
        ];
    }
}
