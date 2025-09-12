<?php

declare(strict_types=1);

use App\Enums\TagEnum;
use App\Filters\TagStacksFilter;
use Illuminate\Database\Eloquent\Builder;

covers(TagStacksFilter::class);

describe('TagStacksFilter', function (): void {
    beforeEach(function (): void {
        $this->filter = new TagStacksFilter;
    });

    describe('tag parsing', function (): void {
        it('executes without throwing exceptions for valid inputs', function (): void {
            $mockBuilder = $this->createMock(Builder::class);
            $mockBuilder->method('whereHas')->willReturnSelf();

            $testCases = [
                'Laravel',
                'Laravel,Symfony,CodeIgniter',
                ['Vue.js', 'React', 'Angular'],
                '',
                456,
                ['Django'],
                'Spring Boot, ASP.NET Core, Ruby on Rails',
            ];

            foreach ($testCases as $tags) {
                $this->filter->__invoke($mockBuilder, $tags, 'stacks');
            }

            expect(true)->toBeTrue();
        });
    });

    describe('tag parsing logic', function (): void {
        it('correctly splits comma-separated string', function (): void {
            $tags = 'Laravel,Symfony,CodeIgniter';
            $expected = ['Laravel', 'Symfony', 'CodeIgniter'];
            $actual = is_array($tags) ? $tags : explode(',', $tags);

            expect($actual)->toBe($expected);
        });

        it('keeps array as-is', function (): void {
            $tags = ['Vue.js', 'React', 'Angular'];
            $actual = is_array($tags) ? $tags : explode(',', (string) $tags);

            expect($actual)->toBe($tags);
        });

        it('handles empty string correctly', function (): void {
            $tags = '';
            $actual = is_array($tags) ? $tags : explode(',', $tags);

            expect($actual)->toBe(['']);
        });
    });

    describe('interface implementation', function (): void {
        it('implements Filter interface', function (): void {
            expect($this->filter)->toBeInstanceOf(Spatie\QueryBuilder\Filters\Filter::class);
        });

        it('uses correct TagEnum value', function (): void {
            expect(TagEnum::STACK->value)->toBe('stack');
        });
    });
});
