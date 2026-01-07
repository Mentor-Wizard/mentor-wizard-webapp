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

    private CarbonInterface $periodStart;

    private CarbonInterface $periodFinish;

    private readonly int $minimumPreBookingTimeInMinutes;

    public function __construct(
        private readonly User $user,
        private readonly string $timezone,
        /** @var array<int, int|string> $excludeEvents */
        private readonly array $excludeEvents = [],
        private readonly bool $excludeSchedule = false,
        private readonly ?MentorProgram $mentorProgram = null,
    ) {
        $this->minimumPreBookingTimeInMinutes = $this->mentorProgram->mentor->minimum_pre_booking_time ?? 0;
    }

    /**
     * @return array<int,array{start: CarbonInterface, end: CarbonInterface}>
     */
    public function getAvailableSlots(): array
    {
        $this->periodStart = Date::now()
            ->addMinutes($this->minimumPreBookingTimeInMinutes)
            ->ceilMinutes(CalendarEvent::ROUNDING_DISCRECY_TIME_IN_MINUTES);
        $currentDateTimezone = Date::now($this->timezone)
            ->addMinutes($this->minimumPreBookingTimeInMinutes)
            ->ceilMinutes(CalendarEvent::ROUNDING_DISCRECY_TIME_IN_MINUTES);
        $this->periodFinish = Date::now($this->timezone)
            ->addMonths(CalendarEvent::MAXIMUM_NUMBER_OF_MONTHS_EVENT_CAN_BE_SET)
            ->ceilMinutes(CalendarEvent::ROUNDING_DISCRECY_TIME_IN_MINUTES);

        $this->getCalendarEvents();

        if ($this->events->isEmpty()) {
            $this->availableSlots[] = [
                'start' => $currentDateTimezone,
                'end'   => $this->periodFinish,
            ];
        } else {
            $this->configureSlots();
        }

        if ($this->excludeSchedule) {
            $this->availableSlots = new ExcludeUserScheduleSchemeService($this->mentorProgram->mentor,
                $this->availableSlots, $this->timezone)->getAvailableSlots();
        }

        return $this->availableSlots;
    }

    protected function getCalendarEvents()
    {
        $ids = [$this->user->id, $this->mentorProgram->mentor_id];
        $calendarEventRequestQuery = CalendarEvent::query()
            ->whereHas('calendarEventUsers', fn ($q) => $q->whereIn('users.id', $ids))
            ->with(['calendarEventUsers' => fn ($q) => $q->whereIn('users.id', $ids)]);

        if (! is_null($this->mentorProgram?->start_time)) {
            $this->periodStart = $this->mentorProgram->start_time->copy()
                ->greaterThanOrEqualTo($this->periodStart)
                ? $this->mentorProgram->start_time : $this->periodStart;
        }

        if (! is_null($this->mentorProgram?->end_time)) {
            $this->periodFinish = $this->mentorProgram->end_time
                ->copy()->lessThanOrEqualTo($this->periodFinish)
                ? $this->mentorProgram->end_time : $this->periodFinish;
        }

        $calendarEventRequestQuery->where('start_date_time', '>=',
            $this->periodStart);
        $calendarEventRequestQuery->where('end_date_time', '<=',
            $this->periodFinish);

        $this->events = $calendarEventRequestQuery
            ->orderBy('start_date_time')
            ->whereKeyNot($this->excludeEvents)
            ->limit(200)
            ->get();
    }

    protected function configureSlots(): void
    {
        $previousEvent = null;
        foreach ($this->events as $event) {
            /** @var CalendarEvent $event */
            if (is_null($previousEvent)) {
                if ($event->start_date_time->greaterThanOrEqualTo($this->periodStart)) {
                    $startSlotPeriod = $this->periodStart->timezone($this->timezone)
                        ->ceilMinutes(CalendarEvent::ROUNDING_DISCRECY_TIME_IN_MINUTES);

                    $endSlotPeriod = $event->start_date_time->timezone($this->timezone)
                        ->ceilMinutes(CalendarEvent::ROUNDING_DISCRECY_TIME_IN_MINUTES);

                    if ($this->mentorProgram->session_duration
                         && $startSlotPeriod->diffInMinutes($endSlotPeriod)
                        < $this->mentorProgram->session_duration
                    ) {
                        continue;
                    }

                    $this->availableSlots[] = [
                        'start' => $startSlotPeriod,
                        'end'   => $endSlotPeriod,
                    ];
                }
            } else {
                $startSlotPeriod = $previousEvent->end_date_time->timezone($this->timezone)
                    ->ceilMinutes(CalendarEvent::ROUNDING_DISCRECY_TIME_IN_MINUTES);
                $endSlotPeriod = $event->start_date_time->timezone($this->timezone)
                    ->ceilMinutes(CalendarEvent::ROUNDING_DISCRECY_TIME_IN_MINUTES);

                if ($this->mentorProgram->session_duration
                    && $startSlotPeriod->diffInMinutes($endSlotPeriod) < $this->mentorProgram->session_duration
                ) {
                    continue;
                }

                $this->availableSlots[] = [
                    'start' => $startSlotPeriod,
                    'end'   => $endSlotPeriod,
                ];
            }

            $previousEvent = $event;
        }

        $this->availableSlots[] = [
            'start' => $previousEvent ? $previousEvent->end_date_time->timezone($this->timezone)
            : $this->periodStart->timezone($this->timezone),
            'end' => $this->periodFinish->timezone($this->timezone),
        ];
    }
}
