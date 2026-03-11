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
            ->join('currencies', 'mentor_profiles.currency_id', '=', 'currencies.id')
            ->orderByRaw('mentor_profiles.rate * currencies.exchange_rate '.$direction)
            ->select('mentor_profiles.*');
    }
}
