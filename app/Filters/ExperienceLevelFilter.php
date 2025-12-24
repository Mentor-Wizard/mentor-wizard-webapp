<?php

declare(strict_types=1);

namespace App\Filters;

use App\Models\MentorProfile;
use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;

/**
 * @implements Filter<MentorProfile>
 */
class ExperienceLevelFilter implements Filter
{
    /**
     * Expected: ?filter[experience]=entry,mid,senior,expert
     *
     * Experience level ranges:
     * - entry: 1-3 years
     * - mid: 4-7 years
     * - senior: 8-12 years
     * - expert: 12+ years
     *
     * @param  array<int,string>|string  $value  Comma-separated or array of experience levels
     * @param  non-empty-string  $property  Filter key (e.g., "experience")
     */
    public function __invoke(Builder $query, mixed $value, string $property): void
    {
        $levels = is_array($value) ? $value : explode(',', (string) $value);

        $query->where(function (Builder $q) use ($levels): void {
            foreach ($levels as $level) {
                $q->orWhere(fn (Builder $sub) => $this->applyRange($sub, mb_trim($level)));
            }
        });
    }

    /**
     * @param  Builder<MentorProfile>  $query
     */
    private function applyRange(Builder $query, string $level): void
    {
        $now = now();

        match ($level) {
            'entry' => $query->whereBetween('experience_started_at', [
                $now->copy()->subYears(3),
                $now->copy()->subYear(),
            ]),
            'mid' => $query->whereBetween('experience_started_at', [
                $now->copy()->subYears(7),
                $now->copy()->subYears(4),
            ]),
            'senior' => $query->whereBetween('experience_started_at', [
                $now->copy()->subYears(12),
                $now->copy()->subYears(8),
            ]),
            'expert' => $query->where('experience_started_at', '<=', $now->copy()->subYears(12)),
            default  => null,
        };
    }
}
