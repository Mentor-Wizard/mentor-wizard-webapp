<?php

declare(strict_types=1);

namespace Modules\Calendar\Services;

use App\Models\MentorProgram;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Modules\Calendar\Enums\CalendarEventStatusEnum;
use Modules\Calendar\Models\CalendarEvent;

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

    private readonly int $sessionDuration;

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
        $this->minimumPreBookingTimeInMinutes = $mentorProfile->minimum_pre_booking_time;
        // Ignoring mutation fo session duration for the time being, as later expected to be used some rules for duration
        $this->sessionDuration = $this->mentorProgram->session_duration ?? 0; // @pest-mutate-ignore
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
            ->whereHas('calendarEventUsers', fn ($q) => $q->whereIn('users.id', $ids));

        $periodStartUtc = $this->periodStart->copy()->timezone('UTC');
        $periodFinishUtc = $this->periodFinish->copy()->timezone('UTC');

        $calendarEventRequestQuery->where(function ($query) use ($periodStartUtc, $periodFinishUtc): void {
            $query->where('end_date_time', '>', $periodStartUtc)
                ->where('start_date_time', '<', $periodFinishUtc);
        });

        $this->events = $calendarEventRequestQuery
            ->where('status', '=', CalendarEventStatusEnum::CONFIRMED->value)
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

                    $slotDuration = $startSlotPeriod->diffInMinutes($endSlotPeriod);
                    // Ignoring mutation: Equivalent mutant - when sessionDuration=0, slotDuration < 0 is always FALSE
                    if ($this->sessionDuration > 0   // @pest-mutate-ignore
                            && $slotDuration
                            < $this->sessionDuration) {
                        $previousEvent = $event;

                        continue;
                    }

                    // Ignoring mutation: Equivalent mutant 5-minute rounding makes 1-minute slots impossible
                    if ($slotDuration <= 0) {   // @pest-mutate-ignore
                        $previousEvent = $event;

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

                // Ignoring mutation: Equivalent mutant - when sessionDuration=0, slotDuration < 0 is always FALSE
                if ($this->sessionDuration > 0    // @pest-mutate-ignore
                    && $startSlotPeriod->diffInMinutes($endSlotPeriod) < $this->sessionDuration
                ) {
                    $previousEvent = $event;

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
