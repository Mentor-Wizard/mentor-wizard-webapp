<?php

declare(strict_types=1);

use App\Traits\ParsesNumericRange;

covers(ParsesNumericRange::class);

describe('ParsesNumericRange trait', function (): void {
    beforeEach(function (): void {
        $this->instance = new class
        {
            use ParsesNumericRange;

            public function testNormalizeBounds(?float $min, ?float $max): array
            {
                return $this->normalizeBounds($min, $max);
            }

            public function testToFloatOrNull(mixed $value): ?float
            {
                return $this->toFloatOrNull($value);
            }
        };
    });

    describe('normalizeBounds method', function (): void {
        it('returns bounds as-is when min <= max', function (): void {
            $result = $this->instance->testNormalizeBounds(10.0, 20.0);
            expect($result)->toBe([10.0, 20.0]);
        });

        it('swaps bounds when min > max', function (): void {
            $result = $this->instance->testNormalizeBounds(20.0, 10.0);
            expect($result)->toBe([10.0, 20.0]);
        });

        it('handles null min value', function (): void {
            $result = $this->instance->testNormalizeBounds(null, 20.0);
            expect($result)->toBe([null, 20.0]);
        });

        it('handles null max value', function (): void {
            $result = $this->instance->testNormalizeBounds(10.0, null);
            expect($result)->toBe([10.0, null]);
        });

        it('handles both null values', function (): void {
            $result = $this->instance->testNormalizeBounds(null, null);
            expect($result)->toBe([null, null]);
        });

        it('handles equal values correctly', function (): void {
            $result = $this->instance->testNormalizeBounds(15.0, 15.0);
            expect($result)->toBe([15.0, 15.0]);
        });
    });

    describe('toFloatOrNull method', function (): void {
        it('returns null for null input', function (): void {
            $result = $this->instance->testToFloatOrNull(null);
            expect($result)->toBeNull();
        });

        it('returns null for empty string', function (): void {
            $result = $this->instance->testToFloatOrNull('');
            expect($result)->toBeNull();
        });

        it('returns null for string with only whitespace', function (): void {
            $result = $this->instance->testToFloatOrNull('   ');
            expect($result)->toBeNull();
        });

        it('converts numeric string to float', function (): void {
            $result = $this->instance->testToFloatOrNull('42.5');
            expect($result)->toBe(42.5);
        });

        it('converts integer string to float', function (): void {
            $result = $this->instance->testToFloatOrNull('42');
            expect($result)->toBe(42.0);
        });

        it('converts integer to float', function (): void {
            $result = $this->instance->testToFloatOrNull(42);
            expect($result)->toBe(42.0);
        });

        it('handles numeric zero integer', function (): void {
            $result = $this->instance->testToFloatOrNull(0);
            expect($result)->toBeNull(); // because empty(0) is true
        });

        it('converts float to float', function (): void {
            $result = $this->instance->testToFloatOrNull(42.5);
            expect($result)->toBe(42.5);
        });

        it('trims whitespace from string before conversion', function (): void {
            $result = $this->instance->testToFloatOrNull('  42.5  ');
            expect($result)->toBe(42.5);
        });

        it('returns null for non-numeric string', function (): void {
            $result = $this->instance->testToFloatOrNull('not a number');
            expect($result)->toBeNull();
        });

        it('returns null for array', function (): void {
            $result = $this->instance->testToFloatOrNull([42]);
            expect($result)->toBeNull();
        });

        it('returns null for object', function (): void {
            $result = $this->instance->testToFloatOrNull((object) ['value' => 42]);
            expect($result)->toBeNull();
        });

        it('handles zero string values with different behaviors', function (): void {
            expect($this->instance->testToFloatOrNull('0'))->toBeNull();
            expect($this->instance->testToFloatOrNull('0.0'))->toBe(0.0);
        });

        it('handles negative values correctly', function (): void {
            expect($this->instance->testToFloatOrNull(-42.5))->toBe(-42.5);
            expect($this->instance->testToFloatOrNull('-42.5'))->toBe(-42.5);
        });
    });
});
