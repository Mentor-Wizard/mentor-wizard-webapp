<?php

declare(strict_types=1);

namespace App\Services\Calendar;

use App\DTO\Calendar\CalendarEventMonthViewData;
use App\Models\CalendarEvent;
use App\Models\User;
use App\Traits\Calendar\BuildsCalendarPayload;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Date;

class MonthCalendarEventsService
{
    use BuildsCalendarPayload;

    /** @var list<array<string, mixed>> */
    private array $calendarView = [];

    public function __construct(
        private readonly User $user,
        private readonly CarbonInterface $date,
        private readonly string $timezone = 'UTC',
    ) {}

    /**
     * @return array{
     *     calendarView: list<array<string, mixed>>,
     *     hasEventsBefore: bool,
     *     hasEventsAfter: bool
     * }
     */
    public function getMonthCalendarEvents(): array
    {
        $dateConfig = $this->prepareDateConfiguration();
        $events = $this->getFormattedEventsForPeriod($dateConfig['startDate'], $dateConfig['endDate']);
        $this->buildCalendarView($dateConfig['monthDates'], $events);

        return [
            'calendarView'    => $this->calendarView,
            'hasEventsBefore' => $this->hasEventsBeforeDate($dateConfig['startDate']),
            'hasEventsAfter'  => $this->hasEventsAfterDate($dateConfig['endDate']),
        ];
    }

    /**
     * @return array{
     *     startDate: CarbonInterface,
     *     endDate: CarbonInterface,
     *     monthDates: list<CarbonInterface>
     * }
     */
    private function prepareDateConfiguration(): array
    {
        // Convert to UTC for database queries
        $utcDate = Date::parse($this->date)->timezone('UTC');
        $startDate = $utcDate->copy()->startOfMonth()->startOfWeek();
        $endDate = $utcDate->copy()->endOfMonth()->endOfWeek();
        $period = CarbonPeriod::create($startDate, '1 day', $endDate);
        $monthDates = array_values(iterator_to_array($period));

        return [
            'startDate'  => $startDate,
            'endDate'    => $endDate,
            'monthDates' => $monthDates,
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function getFormattedEventsForPeriod(CarbonInterface $startDate, CarbonInterface $endDate): array
    {
        /** @var Collection<int, CalendarEvent> $calendarEvents */
        $calendarEvents = $this->user->calendarEvents()
            ->whereBetween('start_date_time', [$startDate, $endDate])
            ->orderBy('start_date_time')
            ->get();

        $calendarEvents->each(function (CalendarEvent $event): void {
            $event->date = Date::parse($event->start_date_time)->timezone($this->timezone)->format('Y-m-d');
        });

        return $calendarEvents->groupBy('date')->map($this->formatDateEvents(...))->all();

    }

    /**
     * @param  list<CarbonInterface>  $monthDates
     * @param  array<string, array<string, mixed>>  $events
     */
    private function buildCalendarView(array $monthDates, array $events): void
    {
        foreach ($monthDates as $monthDate) {
            $dateKey = $monthDate->format('Y-m-d');
            $this->calendarView[] = Arr::has($events, $dateKey)
                ? $events[$dateKey]
                : ['date' => $dateKey, 'calendarEvents' => []];
        }
    }

    /**
     * @param  Collection<int, CalendarEvent>  $dateEvents
     * @return array<string, mixed>
     */
    private function formatDateEvents(Collection $dateEvents): array
    {
        /** @var ?CalendarEvent $firstEvent */
        $firstEvent = $dateEvents->first();

        $formattedEvents = $dateEvents->map(
            fn (CalendarEvent $event): array => CalendarEventMonthViewData::fromModel($event, $this->timezone)->toArray()
        )->all();

        $payload = [
            'date'           => $firstEvent->start_date_time->timezone($this->timezone)->format('Y-m-d'),
            'calendarEvents' => $formattedEvents,
        ];

        $parsedDate = Date::parse($this->date, $this->timezone);
        $eventDate = $firstEvent->start_date_time;

        if ($parsedDate->isSameMonth($eventDate)) {
            $payload['isCurrentMonth'] = true;
        }

        if ($parsedDate->isSameDay($eventDate)) {
            $payload['isSelected'] = true;
        }

        if (Date::now('UTC')->isSameDay($eventDate)) {
            $payload['isToday'] = true;
        }

        return $payload;
    }

    private function hasEventsBeforeDate(CarbonInterface $startDate): bool
    {
        return $this->user->calendarEvents()
            ->where('start_date_time', '<', $startDate)
            ->exists();
    }

    private function hasEventsAfterDate(CarbonInterface $endDate): bool
    {
        return $this->user->calendarEvents()
            ->where('start_date_time', '>', $endDate->endOfDay())
            ->exists();
    }
}
