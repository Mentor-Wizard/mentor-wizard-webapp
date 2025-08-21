<?php

declare(strict_types=1);

namespace App\Filters;

use App\Enums\TagEnum;
use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;

class TagLanguagesFilter implements Filter
{
    public function __invoke(Builder $query, $tags, string $property)
    {
        $tags = is_array($tags) ? $tags : explode(',', (string) $tags);

        return $query->whereHas('mentorTags', function (Builder $query) use ($tags): void {
            $query->where('type', TagEnum::LANGUAGE)
                ->whereIn('tag', $tags);
        });
    }
}
