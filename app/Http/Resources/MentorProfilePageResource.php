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

class MentorProfilePageResource extends JsonResource
{
    const int MENTOR_PER_PAGE = 4;

    public static $wrap;

    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'slug'           => $this->slug,
            'titleBlock'     => $this->titleBlock(),
            'programsBlock'  => MentorProgramsResource::collection($this->mentorPrograms),
            'statisticBlock' => $this->statisticBlock(),
            'reviewBlock'    => MentorReviewResource::collectionWithMentor($this->reviewBlock(), $this->resource),
            'similarMentors' => SimilarMentorResource::collection($this->semilarMentor()),
        ];
    }

    private function titleBlock(): array
    {
        $years = (int) $this->mentorProfile?->experience_started_at->diffInYears(now());

        return [
            'name'        => trim($this->profile->name.' '.$this->profile->last_name),
            'avatar'      => $this->profile->avatar ?: UserProfile::DEFAULT_AVATAR_URL,
            'title'       => $this->mentorProfile?->title,
            'description' => $this->mentorProfile?->description,
            'rate'        => $this->mentorProfile?->rate,
            'currency'    => $this->mentorProfile?->currency->symbol,
            'languages'   => $this->mentorProfile?->languages->pluck('tag')->toArray(),
            'stacks'      => $this->mentorProfile?->stacks->pluck('tag')->toArray(),
            'rating'      => round($this->rating, 1),
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
