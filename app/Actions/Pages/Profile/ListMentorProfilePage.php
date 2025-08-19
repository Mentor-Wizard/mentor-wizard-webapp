<?php

declare(strict_types=1);

namespace App\Actions\Pages\Profile;

use App\Enums\TagEnum;
use App\Models\MentorProfile;
use Illuminate\Database\Eloquent\Builder;
use Lorisleiva\Actions\Concerns\AsController;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ListMentorProfilePage
{
    use AsController;

    public function handle()
    {
        return QueryBuilder::for(MentorProfile::class)
            ->allowedIncludes(['mentorPrograms', 'mentorTags'])
            ->allowedFilters([
                'title',
                'description',
                'mentorPrograms.name',
                'mentorPrograms.description',

                // Range filters
                AllowedFilter::callback('rate', function (Builder $query, $rate): void {
                    $query->whereBetween('rate', [
                        $rate[0] ?? 0,
                        $rate[1] ?? PHP_FLOAT_MAX,
                    ]);
                }),
                AllowedFilter::callback('cost', function (Builder $query, $costs): void {
                    $query->whereHas('mentorPrograms', function (Builder $query) use ($costs): void {
                        $query->whereBetween('cost', [
                            $costs[0] ?? 0,
                            $costs[1] ?? PHP_FLOAT_MAX,
                        ]);
                    });
                }),

                // Filters for tags (mentorTags)
                AllowedFilter::callback('languages', function (Builder $query, $tags): void {
                    $query->whereHas('mentorTags', function (Builder $query) use ($tags): void {
                        $query->where('type', TagEnum::LANGUAGE)
                            ->whereIn('tag', $tags);
                    });
                }),
                AllowedFilter::callback('stacks', function (Builder $query, $tags): void {
                    $tags = is_array($tags) ? $tags : explode(',', $tags);
                    $query->whereHas('mentorTags', function (Builder $query) use ($tags): void {
                        $query->where('type', TagEnum::STACK)
                            ->whereIn('tag', $tags);
                    });
                }),
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
