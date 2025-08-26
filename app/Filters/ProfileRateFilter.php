<?php

declare(strict_types=1);

namespace App\Filters;

use App\Traits\ParsesNumericRange;
use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;

class ProfileRateFilter implements Filter
{
    use ParsesNumericRange;

    /**
     * Expected: ?filter[rate][min]=10&filter[rate][max]=100
     * Also supports only one bound: ?filter[rate][min]=50 or ?filter[rate][max]=80
     *
     * @param  array{min?: string|int|float|null, max?: string|int|float|null}|mixed  $value
     * @param  non-empty-string  $property  Column name to filter (e.g., "rate")
     */
    public function __invoke(Builder $query, mixed $value, string $property): void
    {
        $min = is_array($value) && array_key_exists('min', $value) ? $this->toFloatOrNull($value['min']) : null;
        $max = is_array($value) && array_key_exists('max', $value) ? $this->toFloatOrNull($value['max']) : null;

        [$min, $max] = $this->normalizeBounds($min, $max);

        $query
            ->when($min !== null && $max !== null, fn (Builder $q) => $q->whereBetween('rate', [$min, $max]))
            ->when($min !== null && $max === null, fn (Builder $q) => $q->where('rate', '>=', $min))
            ->when($min === null && $max !== null, fn (Builder $q) => $q->where('rate', '<=', $max));
    }
}
