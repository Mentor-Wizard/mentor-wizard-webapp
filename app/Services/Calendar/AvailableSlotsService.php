<?php

declare(strict_types=1);

namespace App\Services\Calendar;

use App\Models\CalendarEvent;
use App\Models\MentorProgram;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Date;

class AvailableSlotsService
{
    /**
     * @var array<int, array{start: CarbonInterface, end: CarbonInterface}>
     */
    private array $availableSlots = [];

    /**
     * @param  array<int, int|string>  $excludeEvents
     */
    public function __construct(private readonly User $user, private readonly string $timezone,
        private readonly array $excludeEvents = [], private readonly bool $excludeSchedule = false, private readonly ?MentorProgram $mentorProgram = null) {}

    /**
     * @return array<int, array{start: CarbonInterface, end: CarbonInterface}>
     */
    /** @phpstan-ignore-next-line complexity.functionLike */
    public function getAvailableSlots(): array
    {
        $currentDate = Date::now();
        $currentDateTimezone = Date::now($this->timezone);
        $mentorProgramStart = null; // \Carbon\CarbonInterface|null
        $mentorProgramEnd = null;   // \Carbon\CarbonInterface|null

        $calendarEventRequestQuery = $this->user->calendarEvents();
        if (! is_null($this->mentorProgram)) {
            $mentorProgramStart = $this->mentorProgram->start_time;
            $mentorProgramEnd = $this->mentorProgram->end_time;

            if ($mentorProgramStart) {
                $calendarEventRequestQuery->where('start_date_time', '>=', $mentorProgramStart);
            }

            if ($mentorProgramEnd) {
                $calendarEventRequestQuery->where('end_date_time', '<=', $mentorProgramEnd);
            }
        } else {
            $calendarEventRequestQuery->where('start_date_time', '>=', $currentDate);
        }

        $events = $calendarEventRequestQuery
            ->orderBy('start_date_time')
            ->whereKeyNot($this->excludeEvents)
            ->limit(200)
            ->get();

        if ($events->isEmpty()) {
            $this->availableSlots[] = [
                'start' => $mentorProgramStart ? $mentorProgramStart->setTimezone($this->timezone) : $currentDateTimezone,
                'end'   => $mentorProgramEnd ? $mentorProgramEnd->setTimezone($this->timezone) : Date::now($this->timezone)
                    ->addMonths(CalendarEvent::MAXIMUM_NUMBER_OF_MONTHS_EVENT_CAN_BE_SET),
            ];
            if ($this->excludeSchedule) {
                return $this->availableSlots;
            }
        } else {
            $previousEvent = null;
            foreach ($events as $event) {
                /** @var CalendarEvent $event */
                if (is_null($previousEvent)) {
                    if ($event->start_date_time->greaterThanOrEqualTo($currentDate)) {
                        $this->availableSlots[] = ['start' => $currentDateTimezone,
                            'end'                          => $event->start_date_time->setTimezone($this->timezone)];
                    }
                } else {
                    $this->availableSlots[] = ['start' => $previousEvent->end_date_time->setTimezone($this->timezone),
                        'end'                          => $event->start_date_time->setTimezone($this->timezone)];
                }

                $previousEvent = $event;
            }

            $this->availableSlots[] = ['start' => $previousEvent->end_date_time->setTimezone($this->timezone),
                'end'                          => Date::now($this->timezone)->addMonths(CalendarEvent::MAXIMUM_NUMBER_OF_MONTHS_EVENT_CAN_BE_SET)];
        }

        if ($this->excludeSchedule) {
            $this->availableSlots = new ExcludeUserScheduleSchemeService($this->user, $this->availableSlots, $this->timezone)->getAvailableSlots();
        }

        return $this->availableSlots;
    }
}
