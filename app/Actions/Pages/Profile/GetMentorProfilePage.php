<?php

declare(strict_types=1);

namespace App\Actions\Pages\Profile;

use App\Enums\RoleEnum;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Inertia\Inertia;
use Inertia\Response;
use Lorisleiva\Actions\Concerns\AsController;

class GetMentorProfilePage
{
    use AsController;

    const int PER_PAGE = 4;

    public function handle(User $user): Response
    {
        throw_unless($user->hasRole(RoleEnum::MENTOR->value), new ModelNotFoundException);

        $reviews = $user->mentorReviews()
            ->with('menti.profile')
            ->latest()
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return Inertia::render('Profile/Mentor/View', [
            'mentor'        => UserResource::make($user)->resolve(),
            'reviews'       => $reviews,
            'defaultAvatar' => UserProfile::DEFAULT_AVATAR_URL,
        ]);
    }
}
