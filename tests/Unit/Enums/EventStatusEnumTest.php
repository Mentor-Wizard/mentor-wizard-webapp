<?php

declare(strict_types=1);

use App\Enums\EventStatusEnum;

mutates(EventStatusEnum::class);

describe('EventStatusEnum', function (): void {
    it('returns all names and values', function (): void {
        $names = EventStatusEnum::names();
        $values = EventStatusEnum::values();

        $caseNames = array_map(fn ($caseName) => $caseName->name, EventStatusEnum::cases());
        $caseValues = array_map(fn ($caseValue) => $caseValue->value, EventStatusEnum::cases());

        expect($names)->toEqual($caseNames)
            ->and($values)->toEqual($caseValues)
            ->and(array_unique($names))->toHaveCount(count($names))
            ->and(array_unique($values))->toHaveCount(count($values))
            ->and($names)->each->not->toBeEmpty()
            ->and($values)->each->not->toBeEmpty();
    });

    it('contains required statuses', function (): void {
        expect(EventStatusEnum::values())
            ->toContain('Pending Mentor Confirmation')
            ->toContain('Pending Payment')
            ->toContain('Confirmed')
            ->toContain('Finished')
            ->toContain('Cancelled');
    });
});
