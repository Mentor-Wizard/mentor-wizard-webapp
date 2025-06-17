<?php

declare(strict_types=1);

namespace App\Actions\Pages\Profile;

use App\Enums\RoleEnum;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Auth\Access\AuthorizationException;
use Inertia\Inertia;
use Inertia\Response;
use Lorisleiva\Actions\Concerns\AsController;

class GetMentorProfilePage
{
    use AsController;

    const int PER_PAGE = 4;

    public function handle(string $slug): Response
    {
        $mentor = $this->getMentor($slug);

        $reviews = $mentor->mentorReviews()
            ->with('menti.profile')
            ->latest()
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return Inertia::render('Profile/Mentor', [
            'mentor' => UserResource::make($mentor)->resolve(),
            'reviews' => $reviews,
            'defaultAvatar' => UserProfile::DEFAULT_AVATAR_URL,
        ]);
    }
    private function getMentor(string $slug): User
    {
        $user = User::where('slug', $slug)->firstOrFail();
        if ($user->hasRole(RoleEnum::MENTOR->value)) {
            return $user;
        }
        throw new AuthorizationException('Access denied.');
    }
}
