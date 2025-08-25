<?php

declare(strict_types=1);

namespace App\Traits;

trait ParsesNumericRange
{
    /**
     * Normalize min/max so that min <= max when both provided.
     *
     * @return array{0: float|null, 1: float|null}
     */
    protected function normalizeBounds(?float $min, ?float $max): array
    {
        if ($min !== null && $max !== null && $min > $max) {
            return [$max, $min];
        }

        return [$min, $max];
    }

    /**
     * Cast mixed input to float or null.
     */
    protected function toFloatOrNull(mixed $v): ?float
    {
        if ($v === null) {
            return null;
        }

        if (is_string($v)) {
            $v = mb_trim($v);
            if ($v === '') {
                return null;
            }
        }

        return is_numeric($v) ? (float) $v : null;
    }
}
