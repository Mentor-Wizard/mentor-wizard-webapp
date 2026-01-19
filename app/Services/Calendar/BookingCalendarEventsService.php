<?php

declare(strict_types=1);

namespace App\Services\Calendar;

use App\Models\CalendarEvent;
use App\Models\MentorProgram;
use App\Models\User;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Date;

class BookingCalendarEventsService
{
    /**
     * @var array<int, array{
     *   date: string,
     *   slots: array<int, array{start: string, end: string}>,
     *   isSelected?: bool,
     *   isToday?: bool,
     *   isCurrentMonth?: bool,
     * }>
     */
    private array $calendarView = [];

    public function __construct(
        private readonly CarbonInterface $date,
        private readonly User $user,
        private readonly string $timezone,
        private readonly bool $excludeSchedule,
        private readonly MentorProgram $mentorProgram,
    ) {}

    /**
     * @return array{
     *   calendarSlots: array<int, array{
     *     date: string,
     *     slots: array<int, array{start: string, end: string}>,
     *     isSelected?: bool,
     *     isToday?: bool,
     *     isCurrentMonth?: bool,
     *   }>,
     *   hasSlotsBefore: bool,
     *   hasSlotsAfter: bool,
     * }
     */
    public function getFormattedMonthAvailableSlots(): array
    {
        $dateConfig = $this->prepareDateConfiguration();
        $slots = $this->getFormattedEventsSlots();
        $this->buildCalendarView($dateConfig['monthDates'], $slots);

        return [
            'calendarSlots'     => $this->calendarView,
            'hasSlotsBefore'    => $this->hasPreviousSlots(),
            'hasSlotsAfter'     => $this->hasFurtherSlots(),
        ];
    }

    /**
     * @return array{startDate: CarbonInterface, endDate: CarbonInterface, monthDates: array<int, CarbonInterface>}
     */
    private function prepareDateConfiguration(): array
    {
        $startDate = $this->date->copy()->startOfMonth()->startOfWeek();
        $endDate = $this->date->copy()->endOfMonth()->endOfWeek();
        $period = CarbonPeriod::create($startDate, '1 day', $endDate);

        return [
            'startDate'     => $startDate,
            'endDate'       => $endDate,
            'monthDates'    => $period->toArray(),
        ];
    }

    /**
     * @return array<string, array{
     *   date: string,
     *   slots: array<int, array{start: CarbonInterface, end: CarbonInterface}>,
     *   isSelected?: bool,
     *   isToday?: bool,
     *   isCurrentMonth?: bool,
     * }>
     */
    private function getFormattedEventsSlots(): array
    {
        $availableSlots = (new AvailableCalendarEventsSlotsService(
            $this->user,
            $this->timezone,
            $this->mentorProgram,
            [],
            $this->excludeSchedule,
        )
            ->getAvailableSlots());

        /** @var array<int, array{start: CarbonInterface, end: CarbonInterface}> $availableSlots */
        $splitSlots = new SplitSlotsPerSessionDuration(
            $availableSlots,
            $this->mentorProgram->session_duration,
            $this->timezone)
            ->getSplitSlots();

        return collect($splitSlots)->map($this->formatDateEvents(...))->all();
    }

    /**
     * @param  array<int, array{start: CarbonInterface, end: CarbonInterface}>  $dateSlots
     * @return array{
     *   date: string,
     *   slots: array<int, array{start: CarbonInterface, end: CarbonInterface}>,
     *   isSelected?: bool,
     *   isToday?: bool,
     *   isCurrentMonth?: bool,
     * }
     */
    private function formatDateEvents(array $dateSlots): array
    {
        $dateSlots = collect($dateSlots);
        $firstEvent = $dateSlots->first();
        $payload = [
            'date'  => $firstEvent['start']
                ->setTimezone($this->timezone)->format('Y-m-d'),
            'slots' => $dateSlots->all(),
        ];

        $parsedDate = Date::parse($this->date, $this->timezone);
        $eventDate = $firstEvent['start'];

        if ($parsedDate->isSameMonth($eventDate)) {
            $payload['isCurrentMonth'] = true;
        }

        if ($parsedDate->isSameDay($eventDate)) {
            $payload['isSelected'] = true;
        }

        if (Date::now()->isSameDay($eventDate)) {
            $payload['isToday'] = true;
        }

        return $payload;
    }

    /**
     * @param  array<int, CarbonInterface>  $monthDates
     * @param array<string, array{
     *   date: string,
     *   slots: array<int, array{start: CarbonInterface, end: CarbonInterface}>,
     *   isSelected?: bool,
     *   isToday?: bool,
     *   isCurrentMonth?: bool,
     * }> $slots
     * @return array<int, array{
     *   date: string,
     *   slots: array<int, array{start: string, end: string}>,
     *   isSelected?: bool,
     *   isToday?: bool,
     *   isCurrentMonth?: bool,
     * }>
     */
    private function buildCalendarView(array $monthDates, array $slots): array
    {
        foreach ($monthDates as $monthDate) {
            $dateKey = $monthDate->format('Y-m-d');
            $slotsPeriods = [];
            $periods = null;
            if (Arr::has($slots, $dateKey)) {
                $periods = Arr::get($slots, $dateKey);
                foreach ($periods['slots'] as $period) {
                    $slotsPeriods[] = [
                        'start' => $period['start']->format('Y-m-d H:i:s'),
                        'end'   => $period['end']->format('Y-m-d H:i:s'),
                    ];
                }
            }

            $this->calendarView[] = [
                'date'                      => $dateKey,
                'slots'                     => $slotsPeriods,
                'isSelected'                => $periods['isSelected'] ?? false,
                'isToday'                   => $periods['isToday'] ?? false,
                'isCurrentMonth'            => $periods['isCurrentMonth'] ?? false,
            ];
        }

        return $this->calendarView;
    }

    private function hasPreviousSlots(): bool
    {
        return $this->date->greaterThan(Date::now()->startOfMonth());
    }

    private function hasFurtherSlots(): bool
    {
        return $this->date->lessThan(Date::now()
            ->addMonths(CalendarEvent::MAXIMUM_NUMBER_OF_MONTHS_EVENT_CAN_BE_SET)->startOfMonth());
    }
}
