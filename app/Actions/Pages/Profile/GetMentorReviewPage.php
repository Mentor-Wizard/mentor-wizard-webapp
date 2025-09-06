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

    const int EXTRA_PAGE = 1;

    public function handle(User $user, Request $request): JsonResponse
    {
        throw_unless($user->hasRole(RoleEnum::MENTOR->value), new ModelNotFoundException);

        $page = $request->input('page');

        $reviews = $user->mentorReviews()
            ->with('menti')
            ->orderByDesc('created_at')
            ->offset($page * self::PER_PAGE)
            ->limit(self::PER_PAGE + self::EXTRA_PAGE)
            ->get();

        $hasMorePages = $reviews->count() > self::PER_PAGE;
        $items = $reviews->take(self::PER_PAGE);

        return response()->json([
            'items'     => MentorReviewResource::collectionWithMentor($items, $user),
            'next_page' => $hasMorePages ? $page + 1 : null,
        ]);
    }
}
