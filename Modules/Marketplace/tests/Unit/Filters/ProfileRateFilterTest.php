<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Builder;
use Modules\Marketplace\Filters\ProfileRateFilter;
use Modules\Marketplace\Traits\ParsesNumericRange;
use Spatie\QueryBuilder\Filters\Filter;

mutates(ProfileRateFilter::class);

describe('ProfileRateFilter', function (): void {
    beforeEach(function (): void {
        $this->filter = new ProfileRateFilter;
    });

    it('implements Filter interface', function (): void {
        expect($this->filter)->toBeInstanceOf(Filter::class);
    });

    it('uses ParsesNumericRange trait', function (): void {
        $traits = class_uses(ProfileRateFilter::class);
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
        $mockBuilder->method('when')->willReturnSelf();

        $testCases = [
            ['min' => '10', 'max' => '50'],
            ['min' => '25'],
            ['max' => '75'],
            [],
            ['min' => '50', 'max' => '10'], // swapped
            'invalid',
            ['min' => null, 'max' => null],
            ['min' => 'invalid', 'max' => '50'],
        ];

        foreach ($testCases as $value) {
            $this->filter->__invoke($mockBuilder, $value, 'rate');
        }

        expect(true)->toBeTrue();
    });

    it('can be instantiated without dependencies', function (): void {
        $reflection = new ReflectionClass(ProfileRateFilter::class);
        $constructor = $reflection->getConstructor();

        if ($constructor !== null) {
            expect($constructor->getNumberOfRequiredParameters())->toBe(0);
        }

        expect(new ProfileRateFilter)->toBeInstanceOf(ProfileRateFilter::class);
    });
});
