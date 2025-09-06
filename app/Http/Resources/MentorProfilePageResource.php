<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Actions\Pages\Profile\GetMentorProfilePage;
use App\Enums\RoleEnum;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * @property \App\Models\User $resource
 * @property-read \App\Models\MentorProfile|null $mentorProfile
 */
class MentorProfilePageResource extends JsonResource
{
    const int MENTOR_PER_PAGE = 4;

    public static $wrap;

    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->resource->id,
            'slug'           => $this->resource->slug,
            'titleBlock'     => $this->titleBlock(),
            'programsBlock'  => MentorProgramsResource::collection($this->resource->mentorPrograms),
            'statisticBlock' => $this->statisticBlock(),
            'reviewBlock'    => MentorReviewResource::collectionWithMentor($this->reviewBlock(), $this->resource),
            'similarMentors' => SimilarMentorResource::collection($this->semilarMentor()),
        ];
    }

    private function titleBlock(): array
    {
        $years = (int) $this->resource->mentorProfile?->experience_started_at->diffInYears(now());

        return [
            'name'        => mb_trim($this->resource->profile->name.' '.$this->resource->profile->last_name),
            'avatar'      => $this->resource->profile->avatar ?: UserProfile::DEFAULT_AVATAR_URL,
            'title'       => $this->resource->mentorProfile?->title,
            'description' => $this->resource->mentorProfile?->description,
            'rate'        => $this->resource->mentorProfile?->rate,
            'currency'    => $this->resource->mentorProfile?->currency->symbol,
            'languages'   => $this->resource->mentorProfile?->languages->pluck('tag')->toArray(),
            'stacks'      => $this->resource->mentorProfile?->stacks->pluck('tag')->toArray(),
            'rating'      => round($this->resource->rating, 1),
            'reviews'     => $this->mentorReviews()->count(),
            'experience'  => $years.' '.trans_choice('messages.years', $years, ['count' => $years]),
            'mentiCount'  => $this->mentorSessions()->distinct('menti_id')->count(),
        ];
    }

    private function statisticBlock(): array
    {
        return [
            'star_5' => $this->mentorReviews()->where('rating', 5)->count(),
            'star_4' => $this->mentorReviews()->where('rating', 4)->count(),
            'star_3' => $this->mentorReviews()->where('rating', 3)->count(),
            'star_2' => $this->mentorReviews()->where('rating', 2)->count(),
            'star_1' => $this->mentorReviews()->where('rating', 1)->count(),
        ];
    }

    private function reviewBlock(): Collection
    {
        return $this->mentorReviews()
            ->limit(GetMentorProfilePage::PER_PAGE)
            ->orderByDesc('created_at')
            ->get();
    }

    private function semilarMentor(): Collection
    {
        return User::role(RoleEnum::MENTOR)->where('id', '<>', $this->resource->id)->limit(self::MENTOR_PER_PAGE)->get();
    }
}
