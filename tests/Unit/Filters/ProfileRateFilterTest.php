<?php

declare(strict_types=1);

use App\Filters\ProfileRateFilter;
use App\Models\MentorProfile;
use App\Traits\ParsesNumericRange;
use Illuminate\Database\Eloquent\Builder;
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

    describe('SQL generation', function (): void {
        it('generates whereBetween SQL for min+max', function (): void {
            $query = MentorProfile::query();
            (new ProfileRateFilter)($query, ['min' => '20', 'max' => '100'], 'rate');

            expect($query->toSql())->toContain('between')
                ->and($query->getBindings())->toContain(20.0, 100.0);
        });

        it('generates where >= SQL for min only', function (): void {
            $query = MentorProfile::query();
            (new ProfileRateFilter)($query, ['min' => '50'], 'rate');

            expect($query->toSql())->toContain('>= ?')
                ->and($query->getBindings())->toContain(50.0);
        });

        it('generates where <= SQL for max only', function (): void {
            $query = MentorProfile::query();
            (new ProfileRateFilter)($query, ['max' => '80'], 'rate');

            expect($query->toSql())->toContain('<= ?')
                ->and($query->getBindings())->toContain(80.0);
        });

        it('swaps bounds when min > max so between uses smaller as lower bound', function (): void {
            $query = MentorProfile::query();
            (new ProfileRateFilter)($query, ['min' => '80', 'max' => '30'], 'rate');

            expect($query->toSql())->toContain('between')
                ->and($query->getBindings())->toEqual([30.0, 80.0]);
        });

        it('applies no SQL constraint when value is not an array', function (): void {
            $query = MentorProfile::query();
            (new ProfileRateFilter)($query, 'invalid', 'rate');

            expect($query->toSql())->toBe(MentorProfile::query()->toSql());
        });
    });
});
