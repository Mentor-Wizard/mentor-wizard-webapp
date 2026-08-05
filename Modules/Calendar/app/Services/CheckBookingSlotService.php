<?php

declare(strict_types=1);

namespace Modules\Calendar\Services;

use App\Models\MentorProgram;
use Carbon\CarbonImmutable;

readonly class CheckBookingSlotService
{
    public function __construct(
        private CarbonImmutable $requestedStart,
        private CarbonImmutable $requestedEnd,
        private MentorProgram $mentorProgram,
        private int $sessionDurationInMinutes,
    ) {}

    public function isValidSlot(): bool
    {
        $mentor = $this->mentorProgram->mentor;
        $mentorTimezone = $mentor->profile->timezone;

        $availableSlots = new AvailableCalendarEventsSlotsService(
            $mentor,
            $mentorTimezone,
            $this->mentorProgram,
            [],
            true,
        )->getAvailableSlots();

        $splitSlots = new SplitSlotsPerSessionDuration(
            $availableSlots,
            $this->sessionDurationInMinutes,
            $mentorTimezone,
        )->getSplitSlots();

        $reqStartTs = $this->requestedStart->getTimestamp();
        $reqEndTs = $this->requestedEnd->getTimestamp();

        foreach ($splitSlots as $dateSlots) {
            foreach ($dateSlots as $slot) {
                if (
                    $slot['start']->getTimestamp() === $reqStartTs
                    && $slot['end']->getTimestamp() === $reqEndTs
                ) {
                    return true;
                }
            }
        }

        return false;
    }
}
