<?php

declare(strict_types=1);

namespace App\Services\Calendar;

use App\Models\User;
use Carbon\Carbon;

class CheckTimeSlotReservedService
{
    public function __construct(private readonly string $fromDate, private readonly string $fromTime,
        private readonly string $toDate, private readonly string $toTime, private readonly string $timezone,
        private readonly User $user, private readonly array $excludeEvents = []) {}

    public function execute(): bool
    {
        $availableSlots = new GetAvailableSlotsService($this->user, $this->timezone, $this->excludeEvents)->execute();

        if ($availableSlots === []) {
            return true;
        }

        $startDate = Carbon::createFromFormat(
            'Y-m-d H:i',
            $this->fromDate.' '.$this->fromTime,
            $this->timezone
        );
        $endDate = Carbon::createFromFormat(
            'Y-m-d H:i',
            $this->toDate.' '.$this->toTime,
            $this->timezone
        );
        foreach ($availableSlots as $slot) {
            if ($startDate?->greaterThanOrEqualTo($slot['start']) && $endDate?->lessThanOrEqualTo($slot['end'])) {
                return true;
            }
        }

        return false;
    }
}
