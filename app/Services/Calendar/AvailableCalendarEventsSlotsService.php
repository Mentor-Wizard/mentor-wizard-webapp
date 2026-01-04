<?php

declare(strict_types=1);

namespace App\Services\Calendar;

use App\Models\CalendarEvent;
use App\Models\MentorProgram;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;

class AvailableCalendarEventsSlotsService
{
    /** @var array<int, array{start: CarbonInterface, end: CarbonInterface}> */
    private array $availableSlots = [];
    private Collection $events;
    private CarbonInterface|null $mentorProgramStart;
    private CarbonInterface|null $mentorProgramEnd;

    public function __construct(
        private readonly User $user,
        private readonly string $timezone,
        /** @var array<int, int|string> $excludeEvents */
        private readonly array $excludeEvents = [],
        private readonly bool $excludeSchedule = false,
        private MentorProgram|null $mentorProgram = null
    ) {}

    /**
     * @return array<int,array{start: CarbonInterface, end: CarbonInterface}>
     */
    public function getAvailableSlots(): array
    {
        $this->getCalendarEvents();
        $currentDate = Date::now();
        $currentDateTimezone = Date::now($this->timezone);

        if ($this->events->isEmpty()) {
            $this->availableSlots[] = [
                'start' => $currentDateTimezone,
                'end'   => Date::now($this->timezone)
                    ->addMonths(CalendarEvent::MAXIMUM_NUMBER_OF_MONTHS_EVENT_CAN_BE_SET),
            ];
        } else {
            $this->configureSlots($this->events, $currentDateTimezone, $currentDate);
        }

        if ($this->excludeSchedule) {
            $this->availableSlots = new ExcludeUserScheduleSchemeService($this->user,
                $this->availableSlots, $this->timezone)->getAvailableSlots();
        }

        return $this->availableSlots;
    }

    protected function getCalendarEvents(){

        $currentDate = Date::now();
        $calendarEventRequestQuery = $this->user->calendarEvents();
        if (!is_null($this->mentorProgram)) {
            $this->mentorProgramStart = Date::parse($this->mentorProgram?->start_time);
            $this->mentorProgramEnd = Date::parse($this->mentorProgram?->end_time);

            if ($this->mentorProgramStart) {
                $calendarEventRequestQuery->where('start_date_time', '>=', $this->mentorProgramStart);
            }

            if ($this->mentorProgramEnd) {
                $calendarEventRequestQuery->where('end_date_time', '<=', $this->mentorProgramEnd);
            }
        } else {
            $calendarEventRequestQuery->where('start_date_time', '>=', $currentDate);
        }

        $this->events = $calendarEventRequestQuery
            ->orderBy('start_date_time')
            ->whereKeyNot($this->excludeEvents)
            ->limit(200)
            ->get();
    }

    /**
     * @param  Collection<int, mixed>  $events
     */
    protected function configureSlots(Collection $events, CarbonInterface $currentDateTimezone, CarbonInterface $currentDate): void
    {
        $previousEvent = null;
        foreach ($events as $event) {
            /** @var CalendarEvent $event */
            if (is_null($previousEvent)) {
                if ($event->start_date_time->greaterThanOrEqualTo($currentDate)) {
                    $this->availableSlots[] = [
                        'start' => $currentDateTimezone,
                        'end'   => $event->start_date_time->timezone($this->timezone),
                    ];
                }
            } else {
                $this->availableSlots[] = [
                    'start' => $previousEvent->end_date_time->timezone($this->timezone),
                    'end'   => $event->start_date_time->timezone($this->timezone),
                ];
            }
            $this->availableSlots[] = [
                'start' => $this->mentorProgramStart ? $this->mentorProgramStart->setTimezone($this->timezone) : $currentDate,
                'end'   => $this->mentorProgramEnd ? $this->mentorProgramEnd->setTimezone($this->timezone) : Date::now($this->timezone)
                    ->addMonths(CalendarEvent::MAXIMUM_NUMBER_OF_MONTHS_EVENT_CAN_BE_SET),
            ];
//            if ($this->excludeSchedule) {
//                return $this->availableSlots;
//            }
        }

//        else{
//            $previousEvent = null;
//            foreach ($events as $event) {
//                /** @var CalendarEvent $event */
//                if (is_null($previousEvent)) {
//                    if ($event->start_date_time->greaterThanOrEqualTo($currentDate)) {
//                        $this->availableSlots[] = [
//                            'start' => $currentDateTimezone,
//                            'end'   => $event->start_date_time->timezone($this->timezone),
//                        ];
//                    }
//                } else {
//                    $this->availableSlots[] = [
//                        'start' => $previousEvent->end_date_time->timezone($this->timezone),
//                        'end'   => $event->start_date_time->timezone($this->timezone),
//                    ];
//                }
//
//                $previousEvent = $event;
//            }
//
//            $this->availableSlots[] = [
//                'start' => $previousEvent->end_date_time->timezone($this->timezone),
//                'end'   => Date::now($this->timezone)
//                    ->addMonths(CalendarEvent::MAXIMUM_NUMBER_OF_MONTHS_EVENT_CAN_BE_SET),
//            ];
//        }

//        if ($this->excludeSchedule) {
//            $this->availableSlots = new ExcludeUserScheduleSchemeService($this->user,
//                $this->availableSlots, $this->timezone)->getAvailableSlots();
//        }
//
//        return $this->availableSlots;
    }
}
