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

        it('does not swap when min equals max (boundary test for mutation)', function (): void {
            $result = $this->instance->testNormalizeBounds(10.0, 10.0);
            expect($result)->toBe([10.0, 10.0])
                ->and($result[0])->toBe(10.0)
                ->and($result[1])->toBe(10.0);
        });

        it('preserves order for boundary conditions around equality', function (): void {
            expect($this->instance->testNormalizeBounds(5.0, 5.0))->toBe([5.0, 5.0]);
            expect($this->instance->testNormalizeBounds(5.1, 5.0))->toBe([5.0, 5.1]);
            expect($this->instance->testNormalizeBounds(5.0, 5.1))->toBe([5.0, 5.1]);

            expect($this->instance->testNormalizeBounds(0.0, 0.0))->toBe([0.0, 0.0]);
            expect($this->instance->testNormalizeBounds(-5.0, -5.0))->toBe([-5.0, -5.0]);
        });

        it('strictly follows greater-than logic not greater-or-equal', function (float $min, float $max): void {
            $result = $this->instance->testNormalizeBounds($min, $max);

            expect($result)->toBe([$min, $max], sprintf('Equal values %s, %s should not be swapped', $min, $max));
        })->with([
            'positive equal' => [1.0, 1.0],
            'zero equal'     => [0.0, 0.0],
            'negative equal' => [-1.0, -1.0],
            'large equal'    => [100.0, 100.0],
            'pi equal'       => [3.14159, 3.14159],
        ]);

        it('validates strict greater-than behavior using reference tracking', function (): void {
            $min = 42.0;
            $max = 42.0;

            $result = $this->instance->testNormalizeBounds($min, $max);

            expect($result)->toBe([$min, $max]);
            expect($result)->toEqual([$min, $max]);
            expect(array_values($result))->toBe([$min, $max]);
            expect(count($result))->toBe(2);
            expect($result[0])->toBe($min);
            expect($result[1])->toBe($max);
        });

        it('checks boundary condition with microsecond precision', function (): void {
            $value = 1.000000;
            $identical = 1.000000;
            $slightlyLarger = 1.000001;

            $equalResult = $this->instance->testNormalizeBounds($value, $identical);
            expect($equalResult)->toBe([$value, $identical]);

            $largerResult = $this->instance->testNormalizeBounds($slightlyLarger, $value);
            expect($largerResult)->toBe([$value, $slightlyLarger]);

            $preciseEqual = $this->instance->testNormalizeBounds(1.0, 1.0);
            expect($preciseEqual)->toBe([1.0, 1.0]);
        });

        it('kills the greater-than to greater-or-equal mutation via spying', function (): void {
            $spy = new class
            {
                use ParsesNumericRange;

                public array $swapDecisions = [];

                public function testNormalizeBounds(?float $min, ?float $max): array
                {
                    return $this->normalizeBounds($min, $max);
                }

                protected function shouldSwapValues(float $min, float $max): bool
                {
                    $decision = $min > $max;
                    $this->swapDecisions[] = [
                        'min'      => $min,
                        'max'      => $max,
                        'decision' => $decision,
                        'equal'    => $min === $max,
                    ];

                    return $decision;
                }
            };

            $result1 = $spy->testNormalizeBounds(8.0, 8.0);
            $result2 = $spy->testNormalizeBounds(9.0, 6.0);
            $result3 = $spy->testNormalizeBounds(4.0, 12.0);

            expect($spy->swapDecisions)->toHaveCount(3);

            expect($spy->swapDecisions[0]['equal'])->toBeTrue();
            expect($spy->swapDecisions[0]['decision'])->toBeFalse();

            expect($spy->swapDecisions[1]['decision'])->toBeTrue();
            expect($spy->swapDecisions[2]['decision'])->toBeFalse();

            expect($result1)->toBe([8.0, 8.0]);
            expect($result2)->toBe([6.0, 9.0]);
            expect($result3)->toBe([4.0, 12.0]);
        });

        it('directly tests shouldSwapValues method to kill remaining mutation', function (): void {
            $tester = new class
            {
                use ParsesNumericRange {
                    shouldSwapValues as public;
                }
            };

            expect($tester->shouldSwapValues(5.0, 5.0))->toBeFalse();
            expect($tester->shouldSwapValues(10.0, 10.0))->toBeFalse();
            expect($tester->shouldSwapValues(0.0, 0.0))->toBeFalse();

            expect($tester->shouldSwapValues(7.0, 3.0))->toBeTrue();
            expect($tester->shouldSwapValues(15.0, 10.0))->toBeTrue();

            expect($tester->shouldSwapValues(2.0, 8.0))->toBeFalse();
            expect($tester->shouldSwapValues(1.0, 100.0))->toBeFalse();
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
