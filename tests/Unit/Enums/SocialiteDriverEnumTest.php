<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\SocialiteDriverEnum;

describe('SocialiteDriverEnum', function (): void {
    it('returns all values correctly', function (): void {
        $values = SocialiteDriverEnum::values();
        $caseValues = array_map(fn (SocialiteDriverEnum $case) => $case->value, SocialiteDriverEnum::cases());

        expect($values)->toEqual($caseValues)
            ->and(count($values))->toBe(2);
    });

    it('correctly validates drivers', function (): void {
        expect(SocialiteDriverEnum::isValid('google'))->toBeTrue()
            ->and(SocialiteDriverEnum::isValid('github'))->toBeTrue()
            ->and(SocialiteDriverEnum::isValid('facebook'))->toBeFalse()
            ->and(SocialiteDriverEnum::isValid('apple'))->toBeFalse();
    });

    it('has the expected values', function (): void {
        expect(SocialiteDriverEnum::GOOGLE->value)->toBe('google')
            ->and(SocialiteDriverEnum::GITHUB->value)->toBe('github');
    });
});
