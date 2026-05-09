<?php

declare(strict_types=1);

use App\Enums\CalendarSyncStatusEnum;

mutates(CalendarSyncStatusEnum::class);

describe('CalendarSyncStatusEnum', function (): void {
    it('returns all string values via values()', function (): void {
        $values = CalendarSyncStatusEnum::values();

        expect($values)
            ->toBeArray()
            ->toContain('active')
            ->toContain('pending')
            ->toContain('error')
            ->toContain('disconnected')
            ->toHaveCount(4);
    });

    it('contains no duplicate values', function (): void {
        $values = CalendarSyncStatusEnum::values();

        expect(array_unique($values))->toHaveCount(count($values));
    });

    it('every value is a non-empty string', function (): void {
        foreach (CalendarSyncStatusEnum::values() as $value) {
            expect($value)->toBeString()->not->toBeEmpty();
        }
    });
});
