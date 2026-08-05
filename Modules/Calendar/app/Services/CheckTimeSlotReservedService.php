<?php

declare(strict_types=1);

namespace Modules\Calendar\Services;

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
        private MentorProgram $mentorProgram,
        /** @var array<int, int|string> $excludeEvents */
        private array $excludeEvents = [],
    ) {}

    public function isSlotAvailable(): bool
    {
        $availableSlots = new AvailableCalendarEventsSlotsService(
            $this->user,
            $this->timezone,
            $this->mentorProgram,
            $this->excludeEvents,
            true
        )->getAvailableSlots();

        return array_any($availableSlots, fn ($slot): bool => $this->startDateTime->greaterThanOrEqualTo($slot['start'])
            && $this->endDateTime->lessThanOrEqualTo($slot['end']));
    }
}
