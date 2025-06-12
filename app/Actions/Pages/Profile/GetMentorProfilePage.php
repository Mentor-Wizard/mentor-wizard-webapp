<?php

declare(strict_types=1);

namespace App\Actions\Pages\Profile;

use App\Models\UserProfile;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Lorisleiva\Actions\Concerns\AsController;

class GetMentorProfilePage
{
    use AsController;

    public function handle($slag): Response
    {
        return Inertia::render('Profile/Mentor', [
            'mentor' => 'mentor'
        ]);
    }
}
