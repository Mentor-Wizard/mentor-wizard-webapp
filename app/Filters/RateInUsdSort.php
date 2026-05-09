<?php

declare(strict_types=1);

namespace App\Filters;

use App\Models\MentorProfile;
use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Sorts\Sort;

class RateInUsdSort implements Sort
{
    /**
     * @param  Builder<MentorProfile>  $query
     */
    public function __invoke(Builder $query, bool $descending, string $property): void
    {
        $direction = $descending ? 'desc' : 'asc';

        $query
            ->leftJoin('currencies', 'mentor_profiles.currency_id', '=', 'currencies.id')
            ->orderByRaw('COALESCE(mentor_profiles.rate, 0) * COALESCE(currencies.exchange_rate, 1) '.$direction)
            ->select('mentor_profiles.*');
    }
}
