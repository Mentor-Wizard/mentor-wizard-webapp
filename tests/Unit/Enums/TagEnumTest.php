<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\TagEnum;

mutates(TagEnum::class);

describe('TagEnum', function (): void {
    it('has the expected values', function (): void {
        expect(TagEnum::LANGUAGE->value)->toBe('language')
            ->and(TagEnum::STACK->value)->toBe('stack');
    });

    it('has the correct number of cases', function (): void {
        expect(TagEnum::cases())->toHaveCount(2);
    });
});
