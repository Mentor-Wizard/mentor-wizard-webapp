<?php

declare(strict_types=1);

namespace App\Services\UserSchedule;

use App\Enums\UserScheduleRecordType;
use App\Models\UserSchedule;
use Date;
use Illuminate\Support\Arr;

class CheckUserScheduleOverlap
{
    // in view component day off exclusions are set next ot the 7 days of week. So index is 0-6 + 1
    private const int INDEX_OF_EXCLUSION_AT_VUE_COMPONENT = 7;

    /**
     * @var array<int, array<int, mixed>>
     */
    private array $schedulesByDay = [];

    /**
     * @var array<int, mixed>
     */
    private array $dayOffExclusions = [];

    /**
     * @var array<int|string, array<string, string>>
     */
    private array $errors = [];

    /**
     * @param  array<int, mixed>  $schedules
     * @param  array<int, int>  $deleteIds
     */
    public function __construct(
        private array $schedules,
        private readonly array $deleteIds,
        private readonly int $userId) {}

    /**
     * @return array<int|string, array<string, string>>
     */
    public function verifyOverlappingErrors(): array
    {
        $this->combineExistingAndNewSchedules();
        $this->groupSchedulesByday();
        $this->sortScheduleByStartTime();
        $this->checkNumberPerDay();
        foreach ($this->schedulesByDay as $dayOfWeek => $daySchedules) {
            $this->checkWeekDayOverlapping($dayOfWeek, $daySchedules);
        }

        return $this->errors;
    }

    private function combineExistingAndNewSchedules(): void
    {
        $existingSchedules = UserSchedule::query()->where('user_id', '=', $this->userId)
            ->where('type', '!=', UserScheduleRecordType::DAY_OFF->value)
            ->orWhere('type', '=', UserScheduleRecordType::DAY_OFF->value)
            ->where('day_off_date', '>=', Date::today())
            ->get();

        $newIds = Arr::pluck($this->schedules, 'id');
        foreach ($existingSchedules as $existingSchedule) {
            if (! in_array($existingSchedule->id, $newIds) && ! in_array($existingSchedule->id, $this->deleteIds)) {
                $this->schedules[] = $existingSchedule->toArray();
            }
        }
    }

    private function groupSchedulesByday(): void
    {
        foreach ($this->schedules as $index => $schedule) {
            if ($schedule['type'] === UserScheduleRecordType::DAY_OFF->value) {
                $this->dayOffExclusions[] = [
                    'index'   => $index,
                    'id'      => Arr::get($schedule, 'id'),
                    'day_off' => Arr::get($schedule, 'day_off'),
                ];

                continue;
            }

            $dayOfWeek = $schedule['day_of_week'];
            if (! isset($this->schedulesByDay[$dayOfWeek])) {
                $this->schedulesByDay[$dayOfWeek] = [];
            }

            $this->schedulesByDay[$dayOfWeek][] = [
                'index'      => $index,
                'id'         => $schedule['id'] ?? null,
                'start_time' => $schedule['start_time'],
                'end_time'   => $schedule['end_time'],
            ];
        }

    }

    private function checkNumberPerDay(): void
    {
        foreach ($this->schedulesByDay as $index => $daySchedules) {
            if (count($daySchedules) > UserSchedule::MAX_NUMBER_OF_SCHEDULES_PERIODS_PER_DAY) {
                $this->errors[] = [
                    sprintf('schedules.%s.max_schedules_per_day',
                        $index.'.0') => 'There are more than '.UserSchedule::MAX_NUMBER_OF_SCHEDULES_PERIODS_PER_DAY
                        .'  schedules for this day',
                ];
            }
        }

        if (count($this->dayOffExclusions) > UserSchedule::MAX_NUMBER_OF_ACTIVE_DAY_OFF_EXCLUSIONS) {
            $this->errors[] = [
                sprintf('schedules.%s.max_schedules_per_day',
                    self::INDEX_OF_EXCLUSION_AT_VUE_COMPONENT) => 'There are more than '
                    .UserSchedule::MAX_NUMBER_OF_SCHEDULES_PERIODS_PER_DAY
                    .'  schedules for this day',
            ];
        }
    }

    private function sortScheduleByStartTime(): void
    {
        foreach ($this->schedulesByDay as $dayOfWeek => $daySchedules) {
            usort($daySchedules, fn (array $a, array $b): int => $a['start_time'] <=> $b['start_time']);
            $this->schedulesByDay[$dayOfWeek] = $daySchedules;
        }
    }

    /**
     * @param  array<int,mixed>  $daySchedules
     */
    private function checkWeekDayOverlapping(int $dayOfWeek, array $daySchedules): void
    {
        $schedulesCount = count($daySchedules);
        if ($schedulesCount <= UserSchedule::MAX_NUMBER_OF_SCHEDULES_PERIODS_PER_DAY) {
            foreach ($daySchedules as $index => $schedule) {
                $nextSchedule = Arr::get($daySchedules, $index + 1) ?? null;
                if (! $nextSchedule) {
                    continue;
                }

                if (Date::parse($schedule['end_time']) <= Date::parse($nextSchedule['start_time'])) {
                    continue;
                }

                $errorIndex = Arr::get($schedule, 'index') ? $dayOfWeek.'.'.$schedule['index'] : $dayOfWeek;
                $this->errors[] = [
                    sprintf('schedules.%s.start_time',
                        $errorIndex) => 'This time slot overlaps with another schedule on the same day.',
                ];

                return;
            }
        }
    }
}
