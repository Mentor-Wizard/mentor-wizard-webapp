<?php

declare(strict_types=1);

use App\Enums\UserScheduleRecordType;
use Illuminate\Support\Collection;

describe('UserScheduleRecordType', function (): void {
    it('has correct enum values', function (): void {
        expect(UserScheduleRecordType::WORKING_DAY->value)->toBe('Working Day')
            ->and(UserScheduleRecordType::DAY_OFF->value)->toBe('Day off');
    });

    it('returns all enum values as array', function (): void {
        $values = UserScheduleRecordType::values();

        expect($values)->toBeArray()
            ->toHaveCount(2)
            ->toContain('Working Day')
            ->toContain('Day off');
    });

    it('validates valid enum values correctly', function (): void {
        expect(UserScheduleRecordType::isValid('Working Day'))->toBeTrue()
            ->and(UserScheduleRecordType::isValid('Day off'))->toBeTrue();
    });

    it('validates invalid enum values correctly', function (): void {
        expect(UserScheduleRecordType::isValid('Invalid Type'))->toBeFalse()
            ->and(UserScheduleRecordType::isValid('working day'))->toBeFalse()
            ->and(UserScheduleRecordType::isValid(''))->toBeFalse()
            ->and(UserScheduleRecordType::isValid('Holiday'))->toBeFalse();
    });

    it('returns collection with correct structure', function (): void {
        $collection = UserScheduleRecordType::getCollection();

        expect($collection)->toBeInstanceOf(Collection::class)
            ->toHaveCount(2);

        foreach ($collection as $item) {
            expect($item)->toBeArray()
                ->toHaveKeys(['value', 'label'])
                ->and($item['value'])->toBeString()
                ->and($item['label'])->toBeString();
        }
    });

    it('returns collection with matching value and label', function (): void {
        $collection = UserScheduleRecordType::getCollection();

        foreach ($collection as $item) {
            expect($item['value'])->toBe($item['label']);
        }
    });

    it('returns collection containing all enum cases', function (): void {
        $collection = UserScheduleRecordType::getCollection();
        $values = $collection->pluck('value')->all();

        expect($values)->toContain('Working Day')
            ->toContain('Day off');
    });

    it('uses strict comparison in isValid method', function (): void {
        // This ensures the in_array uses strict comparison (third parameter is true)
        expect(UserScheduleRecordType::isValid('1'))->toBeFalse();
    });
});
