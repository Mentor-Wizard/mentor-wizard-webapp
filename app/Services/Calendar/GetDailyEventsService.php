<?php

declare(strict_types=1);

namespace App\Services\Calendar;

use App\Http\Resources\EventDayViewResource;
use App\Models\CalendarEvent;
use App\Models\User;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Date;

class GetDailyEventsService
{
    private array $calendarView = [];

    public function __construct(private readonly User $user, private readonly string $date,
        private readonly string $timezone = 'UTC') {}

    public function execute(): array
    {
        $dateConfig = $this->prepareDailyDateConfiguration();
        $events = $this->getDailyEvents($dateConfig['todayDate'], $dateConfig['tomorrowDate']);
        $this->buildDailyCalendarView($dateConfig['months'], $dateConfig['todayDate'], $dateConfig['daysEvents']);

        return [
            'events'       => $events,
            'calendarView' => $this->calendarView,
        ];
    }

    private function prepareDailyDateConfiguration(): array
    {
        $todayDate = Date::parse($this->date, $this->timezone)->startOfDay();
        $tomorrowDate = Date::parse($this->date, $this->timezone)->addDay()->startOfDay();
        /** @var ?CalendarEvent $firstEvent */
        $firstEvent = $this->user->calendarEvents()->orderBy('start_date_time')->first();
        /** @var ?CalendarEvent $latestEvent */
        $latestEvent = $this->user->calendarEvents()->orderBy('start_date_time', 'desc')->latest()->first();

        $startCalendarMonth = Date::parse($firstEvent->start_date_time ?? $this->date)->setTimezone($this->timezone)->startOfMonth();
        $endCalendarMonth = Date::parse($latestEvent->start_date_time ?? $this->date)->setTimezone($this->timezone)->endOfMonth();
        if ($todayDate->isAfter($endCalendarMonth)) {
            $endCalendarMonth = (clone $todayDate)->endOfMonth();
        }

        $dailyEvents = clone $this->user->calendarEvents()->tap(fn ($collection) => $collection->each(
            fn ($event): string => $event->date = Date::parse($event->start_date_time)->setTimezone($this->timezone)->format('Y-m-d')
        ));

        $period = CarbonPeriod::create($startCalendarMonth, '1 month', $endCalendarMonth);

        return [
            'todayDate'    => $todayDate,
            'tomorrowDate' => $tomorrowDate,
            'months'       => $period->toArray(),
            'daysEvents'   => $dailyEvents->pluck('date')->unique()->toArray(),
        ];
    }

    private function getDailyEvents(CarbonInterface $todayDate, CarbonInterface $tomorrowDate): array
    {
        $todayDateUTC = (clone $todayDate)->setTimezone('UTC');
        $tomorrowDateUTC = (clone $tomorrowDate)->setTimezone('UTC');
        $eventsCollection = $this->user->calendarEvents()
            ->whereBetween('start_date_time', [$todayDateUTC, $tomorrowDateUTC])
            ->orderBy('start_date_time')
            ->get();

        $events = [];
        foreach ($eventsCollection as $dayEvent) {
            /** @var CalendarEvent $dayEvent */
            $events[] = new EventDayViewResource($dayEvent, $this->timezone)->resolve();
        }

        return $events;
    }

    private function buildDailyCalendarView(array $months, CarbonInterface $todayDate, array $daysEvents): void
    {
        foreach ($months as $month) {
            $startDate = Date::parse($month, $this->timezone)->startOfMonth()->startOfWeek();
            $endDate = Date::parse($month, $this->timezone)->endOfMonth()->endOfWeek();
            $daysPeriod = CarbonPeriod::create($startDate, '1 day', $endDate);
            $monthDates = $daysPeriod->toArray();

            foreach ($monthDates as $monthDate) {
                $payload = $this->buildDayPayload($monthDate, $todayDate, $daysEvents);
                if (isset($this->calendarView[$month->format('Y-m')])) {
                    $this->calendarView[$month->format('Y-m')][] = $payload;
                } else {
                    $this->calendarView[$month->format('Y-m')] = [$payload];
                }
            }
        }
    }

    private function buildDayPayload(CarbonInterface $monthDate, CarbonInterface $todayDate, array $daysEvents): array
    {
        $payload = ['date' => $monthDate->format('Y-m-d')];

        if ($monthDate->isSameMonth($todayDate)) {
            $payload['isCurrentMonth'] = true;
        }

        if ($monthDate->isSameDay($todayDate)) {
            $payload['isSelected'] = true;
        }

        if (Date::now($this->timezone)->isSameDay($monthDate)) {
            $payload['isToday'] = true;
        }

        if (in_array($monthDate->format('Y-m-d'), $daysEvents)) {
            $payload['hasEvent'] = true;
        }

        return $payload;
    }
}
