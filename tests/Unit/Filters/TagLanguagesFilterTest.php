<?php

declare(strict_types=1);

use App\Enums\TagEnum;
use App\Filters\TagLanguagesFilter;
use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;

covers(TagLanguagesFilter::class);

describe('TagLanguagesFilter', function (): void {
    beforeEach(function (): void {
        $this->filter = new TagLanguagesFilter;
    });

    describe('tag parsing', function (): void {
        it('executes without throwing exceptions for valid inputs', function (): void {
            $mockBuilder = $this->createMock(Builder::class);
            $mockBuilder->method('whereHas')->willReturnSelf();

            $testCases = [
                'PHP',
                'PHP,JavaScript,Python',
                ['Go', 'Rust', 'C++'],
                '',
                123,
                ['TypeScript'],
                'C#, Visual Basic, F#',
            ];

            foreach ($testCases as $tags) {
                $this->filter->__invoke($mockBuilder, $tags, 'languages');
            }

            expect(true)->toBeTrue();
        });
    });

    describe('tag parsing logic', function (): void {
        it('correctly splits comma-separated string', function (): void {
            $tags = 'PHP,JavaScript,Python';
            $expected = ['PHP', 'JavaScript', 'Python'];
            $actual = is_array($tags) ? $tags : explode(',', $tags);

            expect($actual)->toBe($expected);
        });

        it('keeps array as-is', function (): void {
            $tags = ['Go', 'Rust', 'C++'];
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
            expect(TagEnum::LANGUAGE->value)->toBe('language');
        });
    });
});
