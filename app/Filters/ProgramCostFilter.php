<?php

declare(strict_types=1);

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;

class ProgramCostFilter implements Filter
{
    public function __invoke(Builder $query, $cost, string $property)
    {
        return $query->whereHas('mentorPrograms', function (Builder $query) use ($cost): void {
            $query->whereBetween('cost', [
                $cost[0] ?? 0,
                $cost[1] ?? PHP_FLOAT_MAX,
            ]);
        });
    }
}
