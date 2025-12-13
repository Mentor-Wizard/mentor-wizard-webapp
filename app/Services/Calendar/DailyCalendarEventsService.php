<?php

declare(strict_types=1);

namespace App\Services\Calendar;

use App\DTO\Calendar\CalendarEventDayViewData;
use App\Models\CalendarEvent;
use App\Models\User;
use App\Traits\Calendar\BuildsCalendarPayload;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Date;

class DailyCalendarEventsService
{
    use BuildsCalendarPayload;

    /** @var array<string, array<int, array<string, mixed>>> */
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

    /**
     * @return array{
     *     todayDate: CarbonInterface,
     *     tomorrowDate: CarbonInterface,
     *     months: list<CarbonInterface>,
     *     daysEvents: array<int, string>
     * }
     */
    private function prepareDailyDateConfiguration(): array
    {
        // Convert to UTC for database queries
        $todayDateAsPerTimezone = Date::parse($this->date);
        $tomorrowDate = $todayDateAsPerTimezone->copy()->addDay()->timezone(config('app.timezone'));
        $todayDate = $todayDateAsPerTimezone->copy()->startOfDay()->timezone(config('app.timezone'));

        /** @var Collection<int, CalendarEvent> $dailyEvents */
        $dailyEvents = $this->user->calendarEvents()
            ->where('start_date_time', '>=',
                Date::now()->subMonth()->startOfMonth())
            ->where('start_date_time', '<=', Date::now()->addMonth()->endOfMonth())
            ->get();

        /** @var ?CalendarEvent $firstEvent */
        $firstEvent = (clone $dailyEvents)->sortBy('start_date_time')->first();
        /** @var ?CalendarEvent $latestEvent */
        $latestEvent = (clone $dailyEvents)->last()?->first();

        $startCalendarMonth = Date::parse($firstEvent?->start_date_time ?? $this->date)
            ->timezone($this->timezone)
            ->startOfMonth();
        $endCalendarMonth = Date::parse($latestEvent?->start_date_time ?? $this->date)
            ->timezone($this->timezone)
            ->endOfMonth();

        if ($todayDate->isAfter($endCalendarMonth)) {
            $endCalendarMonth = $todayDate->copy()->endOfMonth();
        }

        //        $dailyEvents = $this->user->calendarEvents()->get();
        /** @var Collection<int, CalendarEvent> $dailyEvents */
        $dailyEvents->each(function (CalendarEvent $event): void {
            $event->date = $event->start_date_time->copy()->timezone($this->timezone)->format('Y-m-d');
        });

        $period = CarbonPeriod::create($startCalendarMonth, '1 month', $endCalendarMonth);
        $months = array_values(iterator_to_array($period));

        return [
            'todayDate'    => $todayDate,
            'tomorrowDate' => $tomorrowDate,
            'months'       => $months,
            'daysEvents'   => array_values($dailyEvents->pluck('date')->unique()->toArray()),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function getDailyEvents(CarbonInterface $todayDate, CarbonInterface $tomorrowDate): array
    {
        $eventsCollection = $this->user->calendarEvents()
            ->with('calendarEventUsers')
            ->whereBetween('start_date_time', [$todayDate, $tomorrowDate])
            ->orderBy('start_date_time')
            ->get();

        $events = [];
        foreach ($eventsCollection as $dayEvent) {
            /** @var CalendarEvent $dayEvent */
            $events[] = CalendarEventDayViewData::fromModel($dayEvent, $this->timezone, $this->user)->toArray();
        }

        return $events;
    }

    /**
     * @param  list<CarbonInterface>  $months
     * @param  array<int, string>  $daysEvents
     */
    private function buildDailyCalendarView(array $months, CarbonInterface $todayDate, array $daysEvents): void
    {
        $referenceDate = $todayDate->copy()->timezone($this->timezone);
        foreach ($months as $month) {
            $monthKey = $month->format('Y-m');
            $period = CarbonPeriod::create(
                $month->copy()->timezone($this->timezone)->startOfMonth()->startOfWeek(),
                '1 day',
                $month->copy()->timezone($this->timezone)->endOfMonth()->endOfWeek()
            );

            $this->calendarView[$monthKey] = collect($period->toArray())
                ->map(fn (CarbonInterface $monthDate): array => $this->buildDayPayload($monthDate, $referenceDate, $daysEvents))
                ->values()
                ->all();
        }
    }
}
