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
     * Expected:filter[rating]=4 (minimum rating)
     *
     * Filters mentors by their average rating from reviews.
     *
     * @param  mixed  $value  Minimum rating value (e.g., 4, 4.5)
     * @param  non-empty-string  $property  Filter key (e.g., "rating")
     */
    public function __invoke(Builder $query, mixed $value, string $property): void
    {
        $minRating = (float) $value;

        $query->whereHas('user.mentorReviews', function (Builder $q): void {
            $q->select('mentor_id');
        })
            ->whereRaw('
                (SELECT AVG(rating)
                 FROM mentor_reviews
                 WHERE mentor_reviews.mentor_id = mentor_profiles.user_id) >= ?
            ', [$minRating]);
    }
}
