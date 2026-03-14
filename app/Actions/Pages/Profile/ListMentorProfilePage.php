<?php

declare(strict_types=1);

namespace App\Actions\Pages\Profile;

use App\Filters\ExperienceLevelFilter;
use App\Filters\ProfileRateFilter;
use App\Filters\ProgramCostFilter;
use App\Filters\RatingFilter;
use App\Filters\TagLanguagesFilter;
use App\Filters\TagStacksFilter;
use App\Models\MentorProfile;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;
use Lorisleiva\Actions\Concerns\AsController;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ListMentorProfilePage
{
    use AsController;

    public function handle(): Response
    {
        // @phpstan-ignore method.notFound (Larastan's with() return type narrows to Builder, losing QueryBuilder type)
        $mentors = QueryBuilder::for(MentorProfile::class)
            ->with([
                'user.profile',
                'currency',
                'user.mentorPrograms' => fn (mixed $query) => $query
                    ->select(['id', 'mentor_id', 'slug', 'is_main']),
            ])
            ->allowedFilters([
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
            ])
            ->allowedSorts([
                'id',
                'rate',
                'experience_started_at',
            ])
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

        return Inertia::render('Profile/MentorListPage', [
            'mentors' => $mentors,
        ]);
    }
}
