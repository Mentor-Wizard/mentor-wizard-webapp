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

    /**
     * @var Collection<int, CalendarEvent>
     */
    private Collection $events;

    private CarbonInterface $periodStart;

    private CarbonInterface $periodFinish;

    private readonly int $minimumPreBookingTimeInMinutes;

    public function __construct(
        private readonly User $user,
        private readonly string $timezone,
        private readonly MentorProgram $mentorProgram,
        /** @var array<int, int|string> $excludeEvents */
        private readonly array $excludeEvents = [],
        private readonly bool $excludeSchedule = false,
    ) {
        $mentor = $this->mentorProgram->mentor;
        $mentorProfile = $mentor->profile;
        $this->minimumPreBookingTimeInMinutes = $mentorProfile->minimum_pre_booking_time ?? 0;
    }

    /**
     * @return array<int,array{start: CarbonInterface, end: CarbonInterface}>
     */
    public function getAvailableSlots(): array
    {

        $this->definePeriodStartAndEnd();
        $this->getCalendarEvents();

        if ($this->events->isEmpty()) {
            $this->availableSlots[] = [
                'start' => $this->periodStart->timezone($this->timezone),
                'end'   => $this->periodFinish->timezone($this->timezone),
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

    public function definePeriodStartAndEnd(): void
    {
        $now = Date::now($this->timezone);
        $this->periodStart = $now->copy();
        if ($this->mentorProgram->start_time) {
            $this->periodStart = $this->mentorProgram->start_time
                ->greaterThanOrEqualTo($now)
                ? $this->mentorProgram->start_time->copy()->timezone($this->timezone)
                : $now->copy();
        }

        $this->periodFinish = Date::now($this->timezone)
            ->addMonths(CalendarEvent::MAXIMUM_NUMBER_OF_MONTHS_EVENT_CAN_BE_SET);

        if ($this->mentorProgram->end_time) {
            $endMentorProgram = $this->mentorProgram->end_time;
            $this->periodFinish = $endMentorProgram->copy()->lessThanOrEqualTo($this->periodFinish)
                ? $endMentorProgram
                : $this->periodFinish;
        }

        $this->periodStart = $this->periodStart->addMinutes($this->minimumPreBookingTimeInMinutes)
            ->ceilMinutes(CalendarEvent::ROUNDING_DISCRECY_TIME_IN_MINUTES);
        $this->periodFinish = $this->periodFinish->subMinutes($this->minimumPreBookingTimeInMinutes)
            ->floorMinutes(CalendarEvent::ROUNDING_DISCRECY_TIME_IN_MINUTES);
    }

    protected function getCalendarEvents(): void
    {
        $ids = [$this->user->getKey(), $this->mentorProgram->mentor_id];
        $calendarEventRequestQuery = CalendarEvent::query()
            ->whereHas('calendarEventUsers', fn ($q) => $q->whereIn('users.id', $ids))
            ->with(['calendarEventUsers' => fn ($q) => $q->whereIn('users.id', $ids)]);

        $calendarEventRequestQuery->where(function ($query): void {
            $query->where('end_date_time', '>', $this->periodStart)
                ->where('start_date_time', '<', $this->periodFinish);
        });

        $this->events = $calendarEventRequestQuery
            ->orderBy('start_date_time')
            ->whereKeyNot($this->excludeEvents)
            ->limit(200)
            ->get();
    }

    /** @phpstan-ignore-next-line complexity.functionLike */
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

                    $sessionDuration = ($this->mentorProgram->session_duration ?? 0);
                    $slotDuration = $startSlotPeriod->diffInMinutes($endSlotPeriod);
                    if ($sessionDuration > 0
                            && $slotDuration
                            < $sessionDuration) {
                        continue;
                    }

                    if ($slotDuration <= 0) {
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

                $sessionDuration = ($this->mentorProgram->session_duration ?? 0);
                if ($sessionDuration > 0
                    && $startSlotPeriod->diffInMinutes($endSlotPeriod) < $sessionDuration
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
            'start' => $previousEvent !== null
                ? $previousEvent->end_date_time->timezone($this->timezone)
                : $this->periodStart->timezone($this->timezone),
            'end' => $this->periodFinish->timezone($this->timezone),
        ];
    }
}
