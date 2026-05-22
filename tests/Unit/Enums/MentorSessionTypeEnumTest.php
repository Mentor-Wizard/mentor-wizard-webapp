<?php

declare(strict_types=1);

use App\Enums\MentorSessionTypeEnum;

mutates(MentorSessionTypeEnum::class);

describe('MentorSessionTypeEnum', function (): void {
    it('returns all names and values', function (): void {
        $names = MentorSessionTypeEnum::names();
        $values = MentorSessionTypeEnum::values();

        $caseNames = array_map(fn (MentorSessionTypeEnum $case) => $case->name, MentorSessionTypeEnum::cases());
        $caseValues = array_map(fn (MentorSessionTypeEnum $case) => $case->value, MentorSessionTypeEnum::cases());

        expect($names)->toEqual($caseNames)
            ->and($values)->toEqual($caseValues)
            ->and(array_unique($names))->toHaveCount(count($names))
            ->and(array_unique($values))->toHaveCount(count($values))
            ->and($names)->each->not->toBeEmpty()
            ->and($values)->each->not->toBeEmpty();
    });

    it('contains required session types', function (): void {
        expect(MentorSessionTypeEnum::values())
            ->toContain('Video Session')
            ->toContain('Voice Session')
            ->toContain('Code Review');
    });
});
