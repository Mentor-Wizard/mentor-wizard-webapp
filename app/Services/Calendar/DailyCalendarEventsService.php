<?php

declare(strict_types=1);

namespace App\Services\Calendar;

use App\Http\Resources\Calendar\CalendarEventDayViewResource;
use App\Models\CalendarEvent;
use App\Models\User;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Date;

class DailyCalendarEventsService
{
    private array $calendarView = [];

    public function __construct(
        private readonly User $user,
        private readonly CarbonInterface $date,
        private readonly string $timezone = 'UTC',
    ) {}

    /**
     * @return array<string, mixed[]>
     */
    public function getDailyCalendarEvents(): array
    {
        $dateConfig = $this->prepareDailyDateConfiguration();
        $events = $this->getDailyEvents($dateConfig['todayDate'], $dateConfig['tomorrowDate']);
        $this->buildDailyCalendarView($dateConfig['months'], $dateConfig['todayDate'], $dateConfig['daysEvents']);

        return [
            'calendarEvents'     => $events,
            'calendarView'       => $this->calendarView,
        ];
    }

    private function prepareDailyDateConfiguration(): array
    {
        // Convert to UTC for database queries
        $utcDate = Date::parse($this->date)->timezone('UTC');
        $todayDate = $utcDate->copy()->startOfDay();
        $tomorrowDate = $utcDate->copy()->addDay()->startOfDay();

        /** @var ?CalendarEvent $firstEvent */
        $firstEvent = $this->user->calendarEvents()->orderBy('start_date_time')->first();
        /** @var ?CalendarEvent $latestEvent */
        $latestEvent = $this->user->calendarEvents()->latest()->first();

        $startCalendarMonth = Date::parse($firstEvent->start_date_time ?? $this->date)
            ->timezone($this->timezone)
            ->startOfMonth();
        $endCalendarMonth = Date::parse($latestEvent->start_date_time ?? $this->date)
            ->timezone($this->timezone)
            ->endOfMonth();

        if ($todayDate->isAfter($endCalendarMonth)) {
            $endCalendarMonth = $todayDate->copy()->endOfMonth();
        }

        /** @var Collection<int, CalendarEvent> $dailyEvents */
        $dailyEvents = $this->user->calendarEvents()->get();
        $dailyEvents->each(function (CalendarEvent $event): void {
            $event->date = $event->start_date_time->copy()->timezone($this->timezone)->format('Y-m-d');
        });

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
        $eventsCollection = $this->user->calendarEvents()
            ->whereBetween('start_date_time', [$todayDate, $tomorrowDate])
            ->orderBy('start_date_time')
            ->get();

        $events = [];
        foreach ($eventsCollection as $dayEvent) {
            /** @var CalendarEvent $dayEvent */
            $events[] = new CalendarEventDayViewResource($dayEvent, $this->timezone)->resolve();
        }

        return $events;
    }

    private function buildDailyCalendarView(array $months, CarbonInterface $todayDate, array $daysEvents): void
    {
        foreach ($months as $month) {
            $monthKey = $month->format('Y-m');
            $period = CarbonPeriod::create(
                $month->copy()->timezone($this->timezone)->startOfMonth()->startOfWeek(),
                '1 day',
                $month->copy()->timezone($this->timezone)->endOfMonth()->endOfWeek()
            );

            $this->calendarView[$monthKey] = collect($period)
                ->map(fn (CarbonInterface $monthDate): array => $this->buildDayPayload($monthDate, $todayDate, $daysEvents))
                ->all();
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
