<?php

declare(strict_types=1);

namespace Modules\Marketplace\Filters;

use Illuminate\Database\Eloquent\Builder;
use Modules\Marketplace\Enums\TagEnum;
use Modules\Marketplace\Models\MentorProfile;
use Spatie\QueryBuilder\Filters\Filter;

/**
 * @implements Filter<MentorProfile>
 */
class TagLanguagesFilter implements Filter
{
    /**
     * Expected: ?filter[languages]=PHP or ?filter[languages]=PHP,Go
     *
     * @param  array<int,string>|string  $tags  Comma-separated or array of language tags
     * @param  non-empty-string  $property  Filter key (e.g., "languages")
     */
    public function __invoke(Builder $query, mixed $tags, string $property): void
    {
        $tags = is_array($tags) ? $tags : explode(',', (string) $tags);

        $query->whereHas('mentorTags', function (Builder $query) use ($tags): void {
            $query->where('type', TagEnum::LANGUAGE)
                ->whereIn('tag', $tags);
        });
    }
}
