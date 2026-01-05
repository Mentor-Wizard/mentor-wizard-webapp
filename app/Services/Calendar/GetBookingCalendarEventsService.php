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
use stdClass;

class GetBookingCalendarEventsService
{
    private array $calendarView = [];

    public function __construct(
        private readonly CarbonInterface $date,
        private readonly User $user,
        private readonly string $timezone,
        private readonly bool $excludeSchedule = false,
        private readonly ?MentorProgram $mentorProgram = null,
    ) {}

    /**
     * @return array<string, bool|mixed[]>
     */
    public function getFormattedMonthAvailableSlots(): array
    {
        $dateConfig = $this->prepareDateConfiguration();
        $slots = $this->getFormattedEventsSlots();
        $this->buildCalendarView($dateConfig['monthDates'], $slots);

        return [
            'calendarSlots'     => $this->calendarView,
            'hasEventsBefore'   => $this->hasPreviousSlots(),
            'hasEventsAfter'    => $this->hasFurtherSlots(),
        ];
    }

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

    private function getFormattedEventsSlots(): array
    {
        $availableSlots = (new AvailableCalendarEventsSlotsService(
            $this->user,
            $this->timezone,
            [],
            $this->excludeSchedule,
            $this->mentorProgram)
            ->getAvailableSlots());

        $splitSlots =  new SplitSlotsPerSessionDuration(
            $availableSlots,
            $this->mentorProgram->session_duration,
        $this->timezone)
            ->getSplitSlots();

        $slotsCollection = collect($splitSlots)->map(fn ($slot): stdClass => (object) ($slot));
        return $slotsCollection->map($this->formatDateEvents(...))->all();
    }

    private function formatDateEvents($dateSlots): array
    {
        $dateSlots = collect($dateSlots);

        $firstEvent = $dateSlots->first();
        $payload = [
            'date'  => $firstEvent['start']->setTimezone($this->timezone)->format('Y-m-d'),
            'slots' => $dateSlots,
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

    private function buildCalendarView(array $monthDates, array $slots): array
    {
        foreach ($monthDates as $monthDate) {
            $dateKey = $monthDate->format('Y-m-d');
            $slotsPeriods = [];
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
                'isSelected'                => isset($periods['isSelected']),
                'isToday'                   => isset($periods['isToday']),
                'isCurrentMonth'            => isset($periods['isCurrentMonth']),
            ];
        }

        return $this->calendarView;
    }

    private function hasPreviousSlots(): bool
    {
        return ! $this->date->greaterThanOrEqualTo(Date::now()
            ->addMonths(CalendarEvent::MAXIMUM_NUMBER_OF_MONTHS_EVENT_CAN_BE_SET));
    }

    private function hasFurtherSlots(): bool
    {
        return ! $this->date->lessThanOrEqualTo(Date::now()
            ->subMonths(CalendarEvent::MAXIMUM_NUMBER_OF_MONTHS_EVENT_CAN_BE_SET));
    }
}
