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
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Lorisleiva\Actions\Concerns\AsController;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ListMentorProfilePage
{
    use AsController;

    /**
     * @return LengthAwarePaginator<int, MentorProfile>
     */
    public function handle(): LengthAwarePaginator
    {
        return QueryBuilder::for(MentorProfile::class)
            ->allowedIncludes(['mentorPrograms', 'mentorTags'])
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
            ->paginate()
            ->appends(request()->query());
    }
}
