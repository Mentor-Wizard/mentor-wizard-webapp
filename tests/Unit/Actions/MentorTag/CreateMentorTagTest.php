<?php

declare(strict_types=1);

use App\Actions\MentorTag\CreateMentorTag;
use App\Enums\TagEnum;
use App\Models\MentorTag;

// Mark which class this test mutates for mutation testing
mutates(CreateMentorTag::class);

describe('CreateMentorTag', function (): void {
    beforeEach(function (): void {
        // Clean slate for each test
        MentorTag::query()->delete();
    });

    it('creates a new mentor tag with proper normalization', function (): void {
        $action = new CreateMentorTag;
        $result = $action->handle('  PHP  ', TagEnum::STACK);

        expect($result)->toBeInstanceOf(MentorTag::class)->and($result->tag)->toBe('php')->and($result->type)->toBe(TagEnum::STACK)->and($result->exists)->toBeTrue()->and(MentorTag::query()->where('tag', 'php')->where('type', TagEnum::STACK)->exists())->toBeTrue();

        // Verify it was saved to a database by checking the model exists
    });

    it('returns existing tag when duplicate is created', function (): void {
        // Create an initial tag
        $existingTag = MentorTag::query()->create([
            'tag'  => 'javascript',
            'type' => TagEnum::STACK,
        ]);

        $action = new CreateMentorTag;
        $result = $action->handle('JavaScript', TagEnum::STACK);

        expect($result->getKey())->toBe($existingTag->getKey())
            ->and($result->tag)->toBe('javascript')
            ->and($result->type)->toBe(TagEnum::STACK);

        // Verify only one record exists
        expect(MentorTag::query()->where('tag', 'javascript')->count())->toBe(1);
    });

    it('creates separate tags for different types', function (): void {
        $action = new CreateMentorTag;

        $stackTag = $action->handle('Python', TagEnum::STACK);
        $languageTag = $action->handle('Python', TagEnum::LANGUAGE);

        expect($stackTag->getKey())->not->toBe($languageTag->getKey())
            ->and($stackTag->type)->toBe(TagEnum::STACK)
            ->and($languageTag->type)->toBe(TagEnum::LANGUAGE)
            ->and($stackTag->tag)->toBe('python')
            ->and($languageTag->tag)->toBe('python');

        // Verify both records exist
        expect(MentorTag::query()->where('tag', 'python')->count())->toBe(2);
    });

    it('handles empty and whitespace-only strings', function (): void {
        $action = new CreateMentorTag;
        $result = $action->handle('   ', TagEnum::STACK);

        expect($result->tag)->toBeEmpty()
            ->and($result->type)->toBe(TagEnum::STACK);
    });

    it('normalizes case consistently', function (): void {
        $action = new CreateMentorTag;

        $tag1 = $action->handle('React', TagEnum::STACK);
        $tag2 = $action->handle('REACT', TagEnum::STACK);
        $tag3 = $action->handle('react', TagEnum::STACK);

        // All should return the same tag instance
        expect($tag1->getKey())->toBe($tag2->getKey())
            ->and($tag2->getKey())->toBe($tag3->getKey())
            ->and($tag1->tag)->toBe('react');

        // Only one record should exist
        expect(MentorTag::query()->where('tag', 'react')->count())->toBe(1);
    });

    it('can be called statically using run method', function (): void {
        $result = CreateMentorTag::run('Vue.js', TagEnum::STACK);

        expect($result)
            ->toBeInstanceOf(MentorTag::class)
            ->and($result->tag)->toBe('vue.js')
            ->and($result->type)->toBe(TagEnum::STACK);
    });
});
