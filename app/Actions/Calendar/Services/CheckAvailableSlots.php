<?php

declare(strict_types=1);

namespace App\Actions\Calendar\Services;

class CheckAvailableSlots
{
    public function __construct(private readonly array $availableSlots, private readonly int $startDateTime, private readonly int $endDateTime) {}

    public function execute(): bool
    {
        if ($this->availableSlots === []) {
            return true;
        }

        foreach ($this->availableSlots as $slot) {
            $start = $slot['start'];
            $end = $slot['end'];
            if ($this->startDateTime >= $start && $this->endDateTime <= $end) {
                return false;
            }
        }

        return false;
    }
}
