<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use Modules\ExternalCalendar\Enums\ExternalCalendarEventSyncStatusEnum;

describe('ExternalCalendarEventSyncStatusEnum', function (): void {
    it('has the expected values', function (): void {
        expect(ExternalCalendarEventSyncStatusEnum::Synced->value)->toBe('synced')
            ->and(ExternalCalendarEventSyncStatusEnum::Error->value)->toBe('error');
    });

    it('has the correct number of cases', function (): void {
        expect(ExternalCalendarEventSyncStatusEnum::cases())->toHaveCount(2);
    });
});
