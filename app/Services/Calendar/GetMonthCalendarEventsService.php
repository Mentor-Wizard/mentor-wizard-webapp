<?php

declare(strict_types=1);

namespace App\Services\Calendar;

use App\Http\Resources\Calendar\CalendarEventMonthViewResource;
use App\Models\CalendarEvent;
use App\Models\User;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Date;

class GetMonthCalendarEventsService
{
    private array $calendarView = [];

    public function __construct(private readonly User $user, private readonly CarbonInterface $date, private readonly string $timezone = 'UTC') {}

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

    private function prepareDateConfiguration(): array
    {
        $startDate = $this->date->copy()->startOfMonth()->startOfWeek();
        $endDate = $this->date->copy()->endOfMonth()->endOfWeek();
        $period = CarbonPeriod::create($startDate, '1 day', $endDate);

        return [
            'startDate'  => $startDate,
            'endDate'    => $endDate,
            'monthDates' => $period->toArray(),
        ];
    }

    private function getFormattedEventsForPeriod(CarbonInterface $startDate, CarbonInterface $endDate): array
    {
        /** @var Collection<int, CalendarEvent> $caledarEvents */
        $caledarEvents = $this->user->calendarEvents()
            ->whereBetween('start_date_time', [$startDate, $endDate])
            ->orderBy('start_date_time')
            ->get();

        $caledarEvents->each(function (CalendarEvent $event): void {
            $event->date = Date::parse($event->start_date_time)->setTimezone($this->timezone)->format('Y-m-d');
        });

        return $caledarEvents->groupBy('date')->map($this->formatDateEvents(...))->all();

    }

    private function buildCalendarView(array $monthDates, array $events): array
    {
        foreach ($monthDates as $monthDate) {
            $dateKey = $monthDate->format('Y-m-d');
            $this->calendarView[] = Arr::has($events, $dateKey)
                ? $events[$dateKey]
                : ['date' => $dateKey, 'calendarEvents' => []];
        }

        return $this->calendarView;
    }

    private function formatDateEvents(Collection $dateEvents): array
    {
        /** @var ?CalendarEvent $firstEvent */
        $firstEvent = $dateEvents->first();
        $payload = [
            'date'           => $firstEvent->start_date_time->setTimezone($this->timezone)->format('Y-m-d'),
            'calendarEvents' => CalendarEventMonthViewResource::collection($dateEvents)
                ->additional(['timeZone' => $this->timezone])
                ->resolve(),
        ];

        $parsedDate = Date::parse($this->date, $this->timezone);
        $eventDate = $firstEvent->start_date_time;

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

    private function hasEventsBeforeDate(CarbonInterface $startDate): bool
    {
        return $this->user->calendarEvents()->where('start_date_time', '<', $startDate->setTimezone('UTC'))->exists();
    }

    private function hasEventsAfterDate(CarbonInterface $endDate): bool
    {
        return $this->user->calendarEvents()->where('start_date_time', '>', $endDate->setTimezone('UTC')->endOfDay())->exists();
    }
}
