<?php

declare(strict_types=1);

namespace App\Services\Calendar;

use App\Models\User;
use Illuminate\Support\Facades\Date;

readonly class CheckTimeSlotReservedService
{
    public function __construct(
        private string $fromDate,
        private string $fromTime,
        private string $toDate,
        private string $toTime,
        private string $timezone,
        private User $user,
        /** @var array<int, int|string> $excludeEvents */
        private array $excludeEvents = [],
    ) {}

    public function isSlotAvailable(): bool
    {
        $availableSlots = new AvailableCalendarEventsSlotsService($this->user, $this->timezone, $this->excludeEvents)->getAvailableSlots();

        if ($availableSlots === []) {
            return true;
        }

        $startDate = Date::createFromFormat(
            'Y-m-d H:i',
            $this->fromDate.' '.$this->fromTime,
            $this->timezone
        );
        $endDate = Date::createFromFormat(
            'Y-m-d H:i',
            $this->toDate.' '.$this->toTime,
            $this->timezone
        );

        return array_any($availableSlots, fn ($slot): bool => $startDate?->greaterThanOrEqualTo($slot['start']) && $endDate?->lessThanOrEqualTo($slot['end']));
    }
}
