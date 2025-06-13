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

    public function handle(string $slag): Response
    {
        $mentor = $this->getMentor($slag);

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
    private function getMentor(string $slag): User
    {
        $user = User::where('slug', $slag)->firstOrFail();
        if ($user->hasRole(RoleEnum::MENTOR->value)) {
            return $user;
        }
        throw new AuthorizationException('mentor', 'Access denied.');
    }
}
