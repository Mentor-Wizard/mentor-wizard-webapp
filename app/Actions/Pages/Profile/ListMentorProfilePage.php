<?php

declare(strict_types=1);

namespace App\Actions\Pages\Profile;

use App\Console\Commands\CacheCategoryTree;
use App\Filters\ExperienceLevelFilter;
use App\Filters\ProfileRateFilter;
use App\Filters\ProgramCostFilter;
use App\Filters\RatingFilter;
use App\Filters\TagLanguagesFilter;
use App\Filters\TagStacksFilter;
use App\Http\Requests\MentorProfile\ListMentorProfileRequest;
use App\Models\MentorProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Inertia\Inertia;
use Inertia\Response;
use Lorisleiva\Actions\Concerns\AsController;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ListMentorProfilePage
{
    use AsController;

    public function handle(ListMentorProfileRequest $request): Response
    {
        $categoryId = $request->integer('category_id') ?: null;
        $categories = CacheCategoryTree::getOrBuild();

        $baseQuery = MentorProfile::query();

        if ($categoryId !== null) {
            $this->applyCategory($baseQuery, $categoryId, $categories);
        }

        // @phpstan-ignore method.notFound (Larastan's with() return type narrows to Builder, losing QueryBuilder type)
        $mentors = QueryBuilder::for($baseQuery)
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
            'mentors'            => $mentors,
            'categories'         => $categories,
            'selectedCategoryId' => $categoryId,
        ]);
    }

    /**
     * Applies a category + descendant filter to the query and returns the cached tree.
     *
     * @param  Builder<MentorProfile>  $query
     * @return array<int, array{id: int, name: string, children: array<mixed>}>
     */
    private function applyCategory(Builder $query, int $categoryId, ?array $categories): void
    {
        $descendantIds = CacheCategoryTree::collectDescendantIds($categories, $categoryId);

        if ($descendantIds !== []) {
            $query->whereHas(
                'categories',
                fn (Builder $q) => $q->whereIn('categories.id', $descendantIds),
            );
        } else {
            $query->whereRaw('1 = 0');
        }
    }
}
