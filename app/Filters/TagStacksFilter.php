<?php

declare(strict_types=1);

namespace App\Filters;

use App\Enums\TagEnum;
use App\Models\MentorProfile;
use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;

/**
 * @implements Filter<MentorProfile>
 */
class TagStacksFilter implements Filter
{
    /**
     * Expected: ?filter[stacks]=Laravel or ?filter[stacks]=Laravel,Symfony
     *
     * @param  array<int,string>|string  $tags  Comma-separated or array of stack tags
     * @param  non-empty-string  $property  Filter key (e.g., "stacks")
     */
    public function __invoke(Builder $query, mixed $tags, string $property): void
    {
        $tags = is_array($tags) ? $tags : explode(',', (string) $tags);

        $query->whereHas('mentorTags', function (Builder $query) use ($tags): void {
            $query->where('type', TagEnum::STACK)
                ->whereIn('tag', $tags);
        });
    }
}
