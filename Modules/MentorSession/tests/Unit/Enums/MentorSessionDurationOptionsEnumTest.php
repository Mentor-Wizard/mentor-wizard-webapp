<?php

declare(strict_types=1);

use Modules\MentorSession\Enums\MentorSessionDurationOptionsEnum;

describe('MentorSessionDurationOptionsEnum', function (): void {
    it('returns all names and values', function (): void {
        $names = MentorSessionDurationOptionsEnum::names();
        $values = MentorSessionDurationOptionsEnum::values();

        $caseNames = array_map(fn (MentorSessionDurationOptionsEnum $case) => $case->name, MentorSessionDurationOptionsEnum::cases());
        $caseValues = array_map(fn (MentorSessionDurationOptionsEnum $case) => $case->value, MentorSessionDurationOptionsEnum::cases());

        expect($names)->toEqual($caseNames)
            ->and($values)->toEqual($caseValues)
            ->and(array_unique($names))->toHaveSameSize($names)
            ->and(array_unique($values))->toHaveSameSize($values)
            ->and($names)->each->not->toBeEmpty()
            ->and($values)->each->toBeGreaterThan(0);
    });

    it('contains required duration options', function (): void {
        expect(MentorSessionDurationOptionsEnum::values())
            ->toContain(15)
            ->toContain(30)
            ->toContain(45)
            ->toContain(60)
            ->toContain(90)
            ->toContain(120);
    });
});
