<?php

declare(strict_types=1);

namespace Modules\Marketplace\Traits;

trait ParsesNumericRange
{
    /**
     * Normalize min/max so that min <= max when both provided.
     *
     * @return array{0: float|null, 1: float|null}
     */
    protected function normalizeBounds(?float $min, ?float $max): array
    {
        if (! is_null($min) && ! is_null($max) && $this->shouldSwapValues($min, $max)) {
            return [$max, $min];
        }

        return [$min, $max];
    }

    protected function shouldSwapValues(float $min, float $max): bool
    {
        return $min > $max;
    }

    /**
     * Cast mixed input to float or null.
     */
    protected function toFloatOrNull(mixed $value): ?float
    {
        if (is_null($value) || empty($value)) {
            return null;
        }

        if (is_string($value)) {
            $value = mb_trim($value);
        }

        return is_numeric($value) ? (float) $value : null;
    }
}
