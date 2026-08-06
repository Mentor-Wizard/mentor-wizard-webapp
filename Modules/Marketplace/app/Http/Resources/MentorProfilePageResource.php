<?php

declare(strict_types=1);

namespace Modules\Marketplace\Http\Resources;

use App\Enums\RoleEnum;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Marketplace\Actions\Pages\GetMentorProfilePage;
use Modules\Marketplace\Models\MentorReview;
use Modules\MentorProgram\Http\Resources\MentorProgramsResource;
use Override;

/**
 * @property User $resource
 */
class MentorProfilePageResource extends JsonResource
{
    const int MENTOR_PER_PAGE = 4;

    #[Override]
    public static $wrap;

    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        /** @var User $user */
        $user = $this->resource;
        $user->loadMissing(['mentorProfile.currency', 'profile', 'mentorReviews', 'mentorSessions']);
        $user->mentorPrograms->loadMissing('currency', 'mentorProgramBlocks');

        return [
            'id'             => $user->id,
            'slug'           => $user->slug,
            'titleBlock'     => $this->titleBlock($user),
            'programsBlock'  => MentorProgramsResource::collection($user->mentorPrograms->where('is_main', false)),
            'statisticBlock' => $this->statisticBlock($user),
            'reviewBlock'    => MentorReviewResource::collectionWithMentor($this->reviewBlock($user), $user),
            'similarMentors' => SimilarMentorResource::collection($this->similarMentor($user)),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function titleBlock(User $user): array
    {
        // @phpstan-ignore-next-line
        $years = (int) $user->mentorProfile?->experience_started_at?->diffInYears(now()) ?? 0;

        return [
            'name'              => mb_trim($user->profile->name.' '.$user->profile->last_name),
            'avatar'            => $user->profile->avatar ?: UserProfile::DEFAULT_AVATAR_URL,
            'title'             => $user->mentorProfile?->title,
            'description'       => $user->mentorProfile?->description,
            'rate'              => $user->mentorProfile?->rate,
            'currency'          => $user->mentorProfile?->currency?->symbol,
            'languages'         => $user->mentorProfile?->languages->pluck('tag')->toArray(),
            'stacks'            => $user->mentorProfile?->stacks->pluck('tag')->toArray(),
            'rating'            => round($user->rating, 1),
            'reviews'           => $user->mentorReviews->count(),
            'experience'        => $years.' '.trans_choice('messages.years', $years, ['count' => $years]),
            'mentiCount'        => $user->mentorSessions->unique('menti_id')->count(),
            'mainProgramSlug'   => $user->mentorPrograms->where('is_main', '=', true)->first()?->slug,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function statisticBlock(User $user): array
    {
        return [
            'star_5' => $user->mentorReviews->where('rating', 5)->count(),
            'star_4' => $user->mentorReviews->where('rating', 4)->count(),
            'star_3' => $user->mentorReviews->where('rating', 3)->count(),
            'star_2' => $user->mentorReviews->where('rating', 2)->count(),
            'star_1' => $user->mentorReviews->where('rating', 1)->count(),
        ];
    }

    /**
     * @return Collection<int, MentorReview>
     */
    private function reviewBlock(User $user): Collection
    {
        return $user->mentorReviews
            ->sortByDesc('created_at')
            ->take(GetMentorProfilePage::PER_PAGE);
    }

    /**
     * @return Collection<int, User>
     */
    private function similarMentor(User $user): Collection
    {
        return User::query()->role(RoleEnum::MENTOR)
            ->with('profile')
            ->where('id', '<>', $user->getKey())
            ->limit(self::MENTOR_PER_PAGE)
            ->get();
    }
}
