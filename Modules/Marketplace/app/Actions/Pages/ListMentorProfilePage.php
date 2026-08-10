<?php

declare(strict_types=1);

namespace Modules\Marketplace\Actions\Pages;

use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;
use Lorisleiva\Actions\Concerns\AsController;
use Modules\Marketplace\Filters\ExperienceLevelFilter;
use Modules\Marketplace\Filters\ProfileRateFilter;
use Modules\Marketplace\Filters\ProgramCostFilter;
use Modules\Marketplace\Filters\RatingFilter;
use Modules\Marketplace\Filters\TagLanguagesFilter;
use Modules\Marketplace\Filters\TagStacksFilter;
use Modules\Marketplace\Models\MentorProfile;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ListMentorProfilePage
{
    use AsController;

    public function handle(): Response
    {
        $mentors = QueryBuilder::for(MentorProfile::class)
            ->with([
                'user.profile',
                'currency',
                'user.mentorPrograms' => fn (mixed $query) => $query
                    ->select(['id', 'mentor_id', 'slug', 'is_main']),
            ])
            // @phpstan-ignore method.notFound (Larastan narrows with() return to Builder, losing Spatie QueryBuilder type)
            ->allowedFilters(
                'title',
                'description',
                'mentorPrograms.name',
                'mentorPrograms.description',

                AllowedFilter::custom('rate', new ProfileRateFilter),
                AllowedFilter::custom('cost', new ProgramCostFilter),
                AllowedFilter::custom('languages', new TagLanguagesFilter),
                AllowedFilter::custom('stacks', new TagStacksFilter),
                AllowedFilter::custom('experience', new ExperienceLevelFilter),
                AllowedFilter::custom('rating', new RatingFilter),
            )
            ->allowedSorts(
                'id',
                'rate',
                'experience_started_at',
            )
            ->paginate(User::DEFAULT_MENTOR_PAGE_PAGINATION)
            ->appends(request()->query())
            ->through(fn (MentorProfile $mentor): array => [
                'title'           => $mentor->title,
                'description'     => $mentor->description,
                'rate'            => $mentor->rate,
                'currency'        => ['symbol' => $mentor->currency?->symbol],
                'userSlug'        => $mentor->user->slug,
                'userName'        => $mentor->user->profile->name,
                'userAvatar'      => $mentor->user->profile->avatar,
                'mainProgramSlug' => $mentor->user->mentorPrograms
                    ->where('is_main', '=', true)->first()?->slug,
            ]);

        return Inertia::render('Marketplace/MentorListPage', [
            'mentors' => $mentors,
        ]);
    }
}
