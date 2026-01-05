<?php

declare(strict_types=1);

namespace App\Services\Calendar;

use App\Models\MentorProgram;
use App\Models\User;
use Carbon\CarbonImmutable;

readonly class CheckTimeSlotReservedService
{
    public function __construct(
        private CarbonImmutable $startDateTime,
        private CarbonImmutable $endDateTime,
        private string $timezone,
        private User $user,
        /** @var array<int, int|string> $excludeEvents */
        private array $excludeEvents = [],
        private ?MentorProgram $mentorProgram = null
    ) {}

    public function isSlotAvailable(): bool
    {
        $availableSlots = new AvailableCalendarEventsSlotsService($this->user,
            $this->timezone, $this->excludeEvents, true,
            $this->mentorProgram
        )->getAvailableSlots();

        return array_any($availableSlots, fn ($slot): bool => $this->startDateTime->greaterThanOrEqualTo($slot['start'])
            && $this->endDateTime->lessThanOrEqualTo($slot['end']));
    }
}
