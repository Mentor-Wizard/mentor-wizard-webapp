<?php

declare(strict_types=1);

namespace App\Services\Calendar;

use App\Models\User;
use App\Models\UserSchedule;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;

class ExcludeUserScheduleSchemeService
{
    /**
     * @var array<int,array{start: CarbonInterface, end: CarbonInterface}>
     */
    private array $scheduleSlots = [];

    /**
     * @var array<array<int|string, mixed>>
     */
    private array $formattedSlots = [];

    /**
     * @var array<int, mixed>
     */
    private array $checkedIntervals;

    private readonly string $scheduleTimezone;

    /**
     * @var array<int, mixed>
     */
    private array $daysOffList = [];

    /**
     * @var Collection<string, EloquentCollection<int, UserSchedule>>
     */
    private readonly Collection $userSchedules;

    /**
     * @param  array<int, array{start: CarbonInterface, end: CarbonInterface}>  $eventsSlots
     */
    public function __construct(
        private readonly User $user,
        private readonly array $eventsSlots,
        private readonly string $chosenTimezone)
    {
        $this->scheduleTimezone = $this->user->profile->timezone;
        $this->userSchedules = $this->user->activeScheduleRecords()->get()->groupBy('type');
    }

    /**
     * @return array<int,array{start: CarbonInterface, end: CarbonInterface}>
     */
    public function getAvailableSlots(): array
    {
        $this->groupUserScheduleByDayAndTypes();
        foreach ($this->eventsSlots as $eventSlot) {
            $this->groupCalendarEventsSlotsPerDay($eventSlot);
        }

        if (isset($this->formattedSlots['Working Day']) && (isset($this->formattedSlots['Working Day']) && $this->formattedSlots['Working Day'] !== [])) {
            foreach ($this->checkedIntervals as $currentInterval) {
                $this->combineCalendarEventsAndUserScheduleSlots($currentInterval);
            }
        }

        return $this->scheduleSlots;
    }

    private function groupUserScheduleByDayAndTypes(): void
    {
        foreach ($this->userSchedules as $type => $schedules) {
            if (isset($this->formattedSlots[$type])) {
                $this->formattedSlots[$type][] = $schedules->groupBy('day_of_week')->toArray();
            } else {
                $this->formattedSlots[$type] = $schedules->groupBy('day_of_week')->toArray();
            }
        }

        if (isset($this->formattedSlots['Day off'])) {
            foreach ($this->formattedSlots['Day off'][1] as $dayOff) {
                $this->daysOffList[] = Date::parse($dayOff['day_off_date'])->format('Y-m-d');
            }
        }
    }

    /**
     * @param  array{start: CarbonInterface, end: CarbonInterface}  $eventSlot
     */
    private function groupCalendarEventsSlotsPerDay(array $eventSlot): void
    {
        $startTime = $eventSlot['start'];
        $endTime = $eventSlot['end'];
        $carbonPeriod = CarbonPeriod::create($startTime->timezone($this->scheduleTimezone), '1 day',
            $endTime->timezone($this->scheduleTimezone));

        foreach ($carbonPeriod as $date) {
            if (($carbonPeriod->count() === 1) && ($date->format('Y-m-d') === $startTime->format('Y-m-d'))) {
                $this->oneDayPeriodFormatting($startTime, $endTime);
            } elseif ($date->format('Y-m-d') === $startTime->format('Y-m-d')) {
                $this->firstDateFormatting($startTime);
            } elseif ($date->format('Y-m-d') === $endTime->format('Y-m-d')) {
                $this->lastDateFormatting($startTime, $endTime);
            } else {
                $this->middleDateFormatting($startTime);
            }
        }
    }

    private function oneDayPeriodFormatting(CarbonInterface $startTime, CarbonInterface $endTime): void
    {
        if ($startTime->format('Y-m-d') !== $endTime->format('Y-m-d')) {
            $this->checkedIntervals[] = [
                'start' => $startTime,
                'end'   => $startTime->endOfDay(),
            ];
            $this->checkedIntervals[] = [
                'start' => clone ($endTime)->startOfDay(),
                'end'   => $endTime,
            ];
        } else {
            $this->checkedIntervals[] = [
                'start' => $startTime,
                'end'   => $endTime,
            ];
        }
    }

    private function firstDateFormatting(CarbonInterface $date): void
    {
        $this->checkedIntervals[] = [
            'start' => $date->copy(),
            'end'   => $date->copy()->endOfDay(),
        ];
    }

    private function lastDateFormatting(CarbonInterface $date, CarbonInterface $endTime): void
    {
        $this->checkedIntervals[] = [
            'start' => $date->copy()->startOfDay(),
            'end'   => $endTime,
        ];
    }

    private function middleDateFormatting(CarbonInterface $date): void
    {
        $this->checkedIntervals[] = [
            'start' => $date->copy()->startOfDay(),
            'end'   => $date->copy()->endOfDay(),
        ];
    }

    /**
     * @param  array{start: CarbonInterface, end: CarbonInterface}  $currentInterval
     */
    private function combineCalendarEventsAndUserScheduleSlots(array $currentInterval): void
    {
        $eventSlotStart = $currentInterval['start'];
        $eventSlotEnd = $currentInterval['end'];
        $checkedDate = ($eventSlotStart)->format('Y-m-d');

        if (in_array($checkedDate, $this->daysOffList)) {
            return;
        }

        $dayOfWeek = $eventSlotStart->dayOfWeek;
        if (isset($this->formattedSlots['Working Day'][$dayOfWeek])) {
            $this->defineCombinedSlots($dayOfWeek, $checkedDate, $eventSlotStart, $eventSlotEnd);
        }
    }

    private function defineCombinedSlots(
        int $dayOfWeek,
        string $checkedDate,
        CarbonInterface $eventSlotStart,
        CarbonInterface $eventSlotEnd): void
    {
        foreach ($this->formattedSlots['Working Day'][$dayOfWeek] as $schedule) {
            $scheduleStartTime = Date::parse(
                $checkedDate
                .$schedule['start_time'],
                $this->scheduleTimezone)
                ->timezone($this->chosenTimezone);

            $scheduleEndTime = Date::parse(
                $checkedDate
                .$schedule['end_time'],
                $this->scheduleTimezone)
                ->timezone($this->chosenTimezone);

            if (! $eventSlotStart->greaterThanOrEqualTo($scheduleEndTime)
                && ! $eventSlotEnd->lessThanOrEqualTo($scheduleStartTime)) {

                $periodStart = ($eventSlotStart->greaterThanOrEqualTo($scheduleStartTime))
                    ? $eventSlotStart->timezone($this->chosenTimezone)
                    : $scheduleStartTime;
                $periodEnd = ($eventSlotEnd->greaterThanOrEqualTo($scheduleEndTime))
                    ? $scheduleEndTime
                    : $eventSlotEnd->timezone($this->chosenTimezone);

                $this->scheduleSlots[] = [
                    'start' => $periodStart,
                    'end'   => $periodEnd,
                ];
            }
        }
    }
}
