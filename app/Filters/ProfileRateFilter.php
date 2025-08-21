<?php

declare(strict_types=1);

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;

class ProfileRateFilter implements Filter
{
    public function __invoke(Builder $query, $rate, string $property)
    {
        return $query->whereBetween('rate', [
            $rate[0] ?? 0,
            $rate[1] ?? PHP_FLOAT_MAX,
        ]);
    }
}
