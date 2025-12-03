<?php

declare(strict_types=1);

namespace App\Services\Calendar;

use App\Models\User;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;

class ExcludeUserScheduleSchemeService
{
    private array $scheduleSlots = [];
    private array $formattedSlots = [];
    private array $checkedIntervals;
    private string $scheduleTimezone;
    private array $listOfExclusions = [];
    private Collection $userSchedules;

    public function __construct(private readonly User $user,
                                private array         $eventsSlots,
                                private string        $chosenTimezone)
    {
    }

    public function getAvailableSlots(): array
    {
        $this->prepareUserSchedule();
        $this->splitEventsAvailableSlotsPerDay();
        $this->compareEventsAndScheduleSlots();
        return $this->scheduleSlots;
    }


    private function prepareUserSchedule(): void
    {
        $schedules = $this->user->activeScheduleRecords();

        $this->scheduleTimezone = (clone $schedules)->first()->first()->timezone ?? config('app.timezone');
        $this->userSchedules = $schedules->get()->groupBy('type');

        foreach ($this->userSchedules as $type => $schedules) {
            if (isset($this->formattedSlots[$type])) {
                $this->formattedSlots[$type][] = $schedules->groupBy('day_of_week')->toArray();
            } else {
                $this->formattedSlots[$type] = $schedules->groupBy('day_of_week')->toArray();
            }
        }

        if (isset($this->formattedSlots['Day off'])) {
            foreach ($this->formattedSlots['Day off'][1] as $dayOff) {
                $this->listOfExclusions[] = Date::parse($dayOff['day_off_date'])->format('Y-m-d');
            }
        }
    }

    private function splitEventsAvailableSlotsPerDay()
    {
        foreach ($this->eventsSlots as $eventSlot) {
            $startTime = $eventSlot['start'];
            $endTime = $eventSlot['end'];
            $carbonPeriod = CarbonPeriod::create($startTime->setTimezone($this->scheduleTimezone), '1 day',
                $endTime->setTimezone($this->scheduleTimezone));
            foreach ($carbonPeriod as $date) {
                if (($date->format('Y-m-d') == $startTime->format('Y-m-d')) && $carbonPeriod->count() == 1) {
                    if ($startTime->format('Y-m-d') != $endTime->format('Y-m-d')) {
                        $this->checkedIntervals[] = ['start' => $startTime, 'end' => $startTime->endOfDay()];;
                        $this->checkedIntervals[] = ['start' => clone($endTime)->startOfDay(), 'end' => $endTime];

                    } else {
                        $this->checkedIntervals[] = ['start' => $startTime, 'end' => $endTime];
                    }
                    $this->checkedIntervals[] = ['start' => $startTime, 'end' => $endTime];
                } else if ($date->format('Y-m-d') == $startTime->format('Y-m-d')) {
                    $this->checkedIntervals[] = ['start' => $date->copy(), 'end' => $date->copy()->endOfDay()];
                } else if ($date->format('Y-m-d') == $endTime->format('Y-m-d')) {
                    $this->checkedIntervals[] = ['start' => $date->copy()->startOfDay(), 'end' => $endTime];
                } else {
                    $this->checkedIntervals[] = ['start' => $date->copy()->startOfDay(), 'end' => $date->copy()->endOfDay()];
                }
            }
        }
    }

    private function compareEventsAndScheduleSlots(): void
    {
        if (isset($this->formattedSlots['Working Day']) && !empty($this->formattedSlots['Working Day'])) {

            foreach ($this->checkedIntervals as $currentInterval) {
                $eventSlotStart = $currentInterval['start'];
                $eventSlotEnd = $currentInterval['end'];
                $checkedDate = ($eventSlotStart)->format('Y-m-d');

                if (in_array($checkedDate, $this->listOfExclusions)) {
                    continue;
                }
                $dayOfWeek = $eventSlotStart->dayOfWeek;

                if (isset($this->formattedSlots['Working Day'][$dayOfWeek])) {
                    foreach ($this->formattedSlots['Working Day'][$dayOfWeek] as $schedule) {
                        $scheduleStartTime = Date::parse($checkedDate . ' ' . $schedule['start_time'], $this->scheduleTimezone)
                            ->setTimezone($this->chosenTimezone ?? config('app.timezone'));
                        $scheduleEndTime = Date::parse($checkedDate . ' ' . $schedule['end_time'], $this->scheduleTimezone)
                            ->setTimezone($this->chosenTimezone ?? config('app.timezone'));
                        if (!$eventSlotStart->greaterThanOrEqualTo($scheduleEndTime) && !$eventSlotEnd->lessThanOrEqualTo($scheduleStartTime)) {
                            $periodStart = ($eventSlotStart->greaterThanOrEqualTo($scheduleStartTime)) ? $eventSlotStart : $scheduleStartTime;
                            $periodEnd = ($eventSlotEnd->greaterThanOrEqualTo($scheduleEndTime)) ? $scheduleEndTime : $eventSlotEnd;
                            $this->scheduleSlots[] = ['start' => $periodStart, 'end' => $periodEnd];
                        }
                    }
                }
            }
        }
    }
}
