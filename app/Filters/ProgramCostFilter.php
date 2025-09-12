<?php

declare(strict_types=1);

namespace App\Filters;

use App\Traits\ParsesNumericRange;
use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;

class ProgramCostFilter implements Filter
{
    use ParsesNumericRange;

    /**
     * Expected: ?filter[program_cost][min]=10&filter[program_cost][max]=100
     *
     * @param  array{min?: string|int|float|null, max?: string|int|float|null}|mixed  $value
     * @param  non-empty-string  $property  Column name to filter (e.g., "cost")
     */
    public function __invoke(Builder $query, mixed $value, string $property): void
    {
        $min = is_array($value) && array_key_exists('min', $value) ? $this->toFloatOrNull($value['min']) : null;
        $max = is_array($value) && array_key_exists('max', $value) ? $this->toFloatOrNull($value['max']) : null;

        [$min, $max] = $this->normalizeBounds($min, $max);

        $query->whereHas('mentorPrograms', function (Builder $q) use ($min, $max): void {
            $q->when($min !== null && $max !== null, fn (Builder $qq) => $qq->whereBetween('cost', [$min, $max]))
                ->when($min !== null && $max === null, fn (Builder $qq) => $qq->where('cost', '>=', $min))
                ->when($min === null && $max !== null, fn (Builder $qq) => $qq->where('cost', '<=', $max));
        });
    }
}
