<?php

declare(strict_types=1);

namespace App\Actions\Pages\Profile;

use App\Enums\RoleEnum;
use App\Http\Resources\MentorReviewResource;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Lorisleiva\Actions\Concerns\AsController;

class GetMentorReviewPage
{
    use AsController;

    const int PER_PAGE = 4;

    public function handle(User $user, Request $request): JsonResponse
    {
        throw_unless($user->hasRole(RoleEnum::MENTOR->value), new ModelNotFoundException);

        $page = (int) $request->input('page');

        return response()->json([
            'items' => MentorReviewResource::collectionWithMentor(
                $user->mentorReviews()
                    ->offset($page * self::PER_PAGE)
                    ->limit(self::PER_PAGE)
                    ->orderByDesc('created_at')
                    ->get(),
                $user
            ),
            'next_page' => $page + 1,
        ]);
    }
}
