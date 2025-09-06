<?php

declare(strict_types=1);

namespace App\Actions\Pages\Profile;

use App\Enums\RoleEnum;
use App\Http\Resources\MentorProfilePageResource;
use App\Models\User;
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

        return Inertia::render('Profile/Mentor/View', [
            'mentor'        => MentorProfilePageResource::make($user),
        ]);
    }
}
