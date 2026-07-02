<?php

declare(strict_types=1);

use App\Filters\ProgramCostFilter;
use App\Traits\ParsesNumericRange;
use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;

mutates(ProgramCostFilter::class);

describe('ProgramCostFilter', function (): void {
    beforeEach(function (): void {
        $this->filter = new ProgramCostFilter;
    });

    it('implements Filter interface', function (): void {
        expect($this->filter)->toBeInstanceOf(Filter::class);
    });

    it('uses ParsesNumericRange trait', function (): void {
        $traits = class_uses(ProgramCostFilter::class);
        expect($traits)->toContain(ParsesNumericRange::class);
    });

    it('has correct __invoke method signature', function (): void {
        $reflection = new ReflectionMethod($this->filter, '__invoke');

        expect($reflection->getNumberOfParameters())->toBe(3)
            ->and($reflection->isPublic())->toBeTrue();

        $params = $reflection->getParameters();
        expect($params[0]->getName())->toBe('query')
            ->and($params[1]->getName())->toBe('value')
            ->and($params[2]->getName())->toBe('property');
    });

    it('processes various input formats without errors', function (): void {
        $mockBuilder = $this->createMock(Builder::class);
        $mockBuilder->method('whereHas')->willReturnSelf();

        $testCases = [
            ['min' => '100.0', 'max' => '500.0'],
            ['min' => '250.5'],
            ['max' => '1000.0'],
            ['min' => '1000', 'max' => '100'], // swapped
            [],
            'invalid',
            ['min' => null, 'max' => null],
            ['min' => 'invalid', 'max' => '50'],
        ];

        foreach ($testCases as $value) {
            $this->filter->__invoke($mockBuilder, $value, 'cost');
        }

        expect(true)->toBeTrue();
    });

    it('can be instantiated without dependencies', function (): void {
        $reflection = new ReflectionClass(ProgramCostFilter::class);
        $constructor = $reflection->getConstructor();

        if ($constructor !== null) {
            expect($constructor->getNumberOfRequiredParameters())->toBe(0);
        }

        expect(new ProgramCostFilter)->toBeInstanceOf(ProgramCostFilter::class);
    });
});
