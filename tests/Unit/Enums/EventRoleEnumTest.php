<?php

declare(strict_types=1);

use App\Enums\EventRoleEnum;

mutates(EventRoleEnum::class);

describe('EventRoleEnum', function (): void {
    it('returns all names and values', function (): void {
        $names = EventRoleEnum::names();
        $values = EventRoleEnum::values();

        $caseNames = array_map(fn (EventRoleEnum $case) => $case->name, EventRoleEnum::cases());
        $caseValues = array_map(fn (EventRoleEnum $case) => $case->value, EventRoleEnum::cases());

        expect($names)->toEqual($caseNames)
            ->and($values)->toEqual($caseValues)
            ->and(array_unique($names))->toHaveCount(count($names))
            ->and(array_unique($values))->toHaveCount(count($values))
            ->and($names)->each->not->toBeEmpty()
            ->and($values)->each->not->toBeEmpty();
    });

    it('contains required roles', function (): void {
        expect(EventRoleEnum::values())
            ->toContain('Host')
            ->toContain('Co-Host')
            ->toContain('Menti')
            ->toContain('Moderator');
    });
});
