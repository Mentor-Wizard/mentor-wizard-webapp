<?php

declare(strict_types=1);

use App\Enums\CalendarSyncStatusEnum;

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

        expect(array_unique($values))->toHaveSameSize($values);
    });

    it('every value is a non-empty string', function (): void {
        expect(CalendarSyncStatusEnum::values())->each->toBeString()->not->toBeEmpty();
    });
});
