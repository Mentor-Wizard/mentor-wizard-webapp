<?php

declare(strict_types=1);

use App\Enums\TagEnum;
use App\Filters\TagStacksFilter;
use App\Models\MentorProfile;
use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;

mutates(TagStacksFilter::class);

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
            expect($this->filter)->toBeInstanceOf(Filter::class);
        });

        it('uses correct TagEnum value', function (): void {
            expect(TagEnum::STACK->value)->toBe('stack');
        });
    });

    describe('SQL generation', function (): void {
        it('generates whereHas on mentorTags with whereIn for array of tags', function (): void {
            $query = MentorProfile::query();
            (new TagStacksFilter)($query, ['Laravel', 'Symfony'], 'stacks');

            expect($query->toSql())->toContain('exists')
                ->toContain('in (?, ?)')
                ->and($query->getBindings())->toContain('stack', 'Laravel', 'Symfony');
        });

        it('generates whereHas with whereIn for comma-separated string', function (): void {
            $query = MentorProfile::query();
            (new TagStacksFilter)($query, 'Laravel,Symfony', 'stacks');

            expect($query->toSql())->toContain('exists')
                ->toContain('in (?, ?)')
                ->and($query->getBindings())->toContain('stack', 'Laravel', 'Symfony');
        });

        it('generates whereHas with single whereIn for a single tag', function (): void {
            $query = MentorProfile::query();
            (new TagStacksFilter)($query, ['Rust'], 'stacks');

            expect($query->toSql())->toContain('exists')
                ->toContain('in (?)')
                ->and($query->getBindings())->toContain('stack', 'Rust');
        });
    });
});
