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
        $request->user()->email = $request->get('email');

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->email = $request->get('email');;
        $request->user()->save();
        $request->user()->profile()->updateOrCreate([], $this->dataUpdate($request));

        if ($request->hasFile('avatar')) {

            $request->user()
                ->profile
                ->clearMediaCollection('avatar');

            $request->user()
                ->profile
                ->addMedia($request->file('avatar'))
                ->toMediaCollection('avatar');
        }

        return redirect()->route('profile.edit');
    }

    private function dataUpdate(UpdateProfileRequest $request): array
    {
        return [
            'name' => $request->get('name'),
            'last_name' => $request->get('last_name'),
        ];
    }
}
