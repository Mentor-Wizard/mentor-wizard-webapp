<?php

declare(strict_types=1);

namespace Modules\Marketplace\Actions\Pages;

use App\Enums\RoleEnum;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Lorisleiva\Actions\Concerns\AsController;
use Modules\Marketplace\Http\Resources\MentorReviewResource;

class GetMentorReviewPage
{
    use AsController;

    const int PER_PAGE = 4;

    const int EXTRA_PAGE = 1;

    public function handle(User $mentor, Request $request): JsonResponse
    {
        throw_unless($mentor->hasRole(RoleEnum::MENTOR->value), ModelNotFoundException::class);

        $page = $request->input('page');

        $reviews = $mentor->mentorReviews()
            ->with('menti')->latest()
            ->offset($page * self::PER_PAGE)
            ->limit(self::PER_PAGE + self::EXTRA_PAGE)
            ->get();

        $hasMorePages = $reviews->count() > self::PER_PAGE;
        $items = $reviews->take(self::PER_PAGE);

        return response()->json([
            'items'     => MentorReviewResource::collectionWithMentor($items, $mentor),
            'next_page' => $hasMorePages ? $page + 1 : null,
        ]);
    }
}
