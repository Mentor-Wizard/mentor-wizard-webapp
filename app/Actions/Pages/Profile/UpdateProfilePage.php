<?php

declare(strict_types=1);

namespace App\Actions\Pages\Profile;

use App\Http\Requests\Profile\UpdateProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
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
        $request->user()->email = Arr::get($request, 'email');

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->email = Arr::get($request, 'email');
        $request->user()->save();
        $request->user()->profile()->updateOrCreate([], $this->dataUpdate($request));

        if ($request->hasFile('logo')) {

            $request->user()
                ->profile
                ->clearMediaCollection('avatar');

            $request->user()
                ->profile
                ->addMedia($request->file('logo'))
                ->toMediaCollection('avatar');
        }

        return redirect()->route('profile.edit');
    }

    public function dataUpdate(UpdateProfileRequest $request)
    {
        return [
            'name'      => Arr::get($request, 'name'),
            'last_name' => Arr::get($request, 'last_name'),
        ];
    }
}
