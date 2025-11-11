<?php

declare(strict_types=1);

namespace App\Services\Calendar;

use App\Models\CalendarEvent;
use App\Models\User;
use Illuminate\Support\Carbon;

class GetAvailableSlotsService
{
    private array $availableSlots = [];

    public function __construct(private readonly User $user, private readonly string $timezone, private readonly array $excludeEvents = []) {}

    public function execute()
    {
        $currentDate = Carbon::now();
        $currentDateTimezone = Carbon::now($this->timezone);

        $events = $this->user->calendarEvents()
            ->where('start_date_time', '>=', $currentDate)->orderBy('start_date_time')
            ->whereKeyNot($this->excludeEvents)
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
                        'end'                          => $event->start_date_time->setTimezone($this->timezone)];
                }
            } else {
                $this->availableSlots[] = ['start' => $previousEvent->end_date_time->setTimezone($this->timezone),
                    'end'                          => $event->start_date_time->setTimezone($this->timezone)];
            }

            $previousEvent = $event;
        }

        $this->availableSlots[] = ['start' => $previousEvent->end_date_time->setTimezone($this->timezone),
            'end'                          => Carbon::now($this->timezone)->addMonths(CalendarEvent::MAXIMUM_NUMBER_OF_MONTHS_EVENT_CAN_BE_SET)];

        return $this->availableSlots;
    }
}
