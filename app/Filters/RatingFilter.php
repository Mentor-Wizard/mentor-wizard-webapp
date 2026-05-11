<?php

declare(strict_types=1);

namespace App\Filters;

use App\Models\MentorProfile;
use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;

/**
 * @implements Filter<MentorProfile>
 */
class RatingFilter implements Filter
{
    /**
     * Expected: filter[rating]=4 (minimum average rating)
     *
     * @param  mixed  $value  Minimum rating value (e.g., 3, 4, 5)
     * @param  non-empty-string  $property  Filter key (e.g., "rating")
     */
    public function __invoke(Builder $query, mixed $value, string $property): void
    {
        $minRating = is_numeric($value) ? (float) $value : 1.0; // @pest-mutate-ignore

        $query->whereHas('user', function (Builder $userQuery) use ($minRating): void {
            $userQuery->whereIn('users.id', function ($sub) use ($minRating): void {
                $sub->select('mentor_id')
                    ->from('mentor_reviews')
                    ->groupBy('mentor_id')
                    ->havingRaw('AVG(rating) >= ?', [$minRating]);
            });
        });
    }
}
