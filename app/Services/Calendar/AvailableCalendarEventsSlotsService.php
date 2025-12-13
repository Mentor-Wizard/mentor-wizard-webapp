<?php

declare(strict_types=1);

namespace App\Services\Calendar;

use App\Models\CalendarEvent;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Date;

class AvailableCalendarEventsSlotsService
{
    /** @var list<array{start: CarbonInterface, end: CarbonInterface}> */
    private array $availableSlots = [];

    public function __construct(
        private readonly User $user,
        private readonly string $timezone,
        /** @var array<int, int|string> $excludeEvents */
        private readonly array $excludeEvents = [],
    ) {}

    /**
     * @return list<array{start: CarbonInterface, end: CarbonInterface}>
     */
    public function getAvailableSlots(): array
    {
        $currentDate = Date::now();
        $currentDateTimezone = Date::now($this->timezone);

        $events = $this->user->calendarEvents()
            ->where('start_date_time', '>=', $currentDate)->orderBy('start_date_time')
            ->whereKeyNot($this->excludeEvents)
            ->limit(100)
            ->get();

        if ($events->isEmpty()) {
            return [];
        }

        $previousEvent = null;
        foreach ($events as $event) {
            /** @var CalendarEvent $event */
            if (is_null($previousEvent)) {
                if ($event->start_date_time->greaterThanOrEqualTo($currentDate)) {
                    $this->availableSlots[] = ['start' => $currentDateTimezone,
                        'end'                          => $event->start_date_time->timezone($this->timezone)];
                }
            } else {
                $this->availableSlots[] = ['start' => $previousEvent->end_date_time->timezone($this->timezone),
                    'end'                          => $event->start_date_time->timezone($this->timezone)];
            }

            $previousEvent = $event;
        }

        $this->availableSlots[] = ['start' => $previousEvent->end_date_time->timezone($this->timezone),
            'end'                          => Date::now($this->timezone)
                ->addMonths(CalendarEvent::MAXIMUM_NUMBER_OF_MONTHS_EVENT_CAN_BE_SET)];

        if ($this->excludeSchedule) {
            $this->availableSlots = new ExcludeUserScheduleSchemeService($this->user, $this->availableSlots, $this->timezone)->getAvailableSlots();
        }

        return $this->availableSlots;
    }
}
