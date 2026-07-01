<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\ExternalCalendarEventLogTypeEnum;

describe('ExternalCalendarEventLogTypeEnum', function (): void {
    it('has the expected values', function (): void {
        expect(ExternalCalendarEventLogTypeEnum::Error->value)->toBe('error')
            ->and(ExternalCalendarEventLogTypeEnum::Success->value)->toBe('success')
            ->and(ExternalCalendarEventLogTypeEnum::Info->value)->toBe('info')
            ->and(ExternalCalendarEventLogTypeEnum::ErrorStatusViewed->value)->toBe('error_status_viewed');
    });

    it('has the correct number of cases', function (): void {
        expect(ExternalCalendarEventLogTypeEnum::cases())->toHaveCount(4);
    });
});
