<?php

declare(strict_types=1);

namespace App\Actions\Pages\Profile;

use App\Models\UserProfile;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Inertia\Inertia;
use Inertia\Response;
use Lorisleiva\Actions\Concerns\AsController;

class GetProfilePage
{
    use AsController;

    public function handle(): Response
    {
        $user = auth()->user();

        return Inertia::render('Profile/EditPage', [
            'mustVerifyEmail' => $user instanceof MustVerifyEmail, // @pest-mutate-ignore
            'status'          => session('status'),
            'avatar'          => $user->profile->avatar ?: UserProfile::DEFAULT_AVATAR_URL,
        ]);
    }
}
