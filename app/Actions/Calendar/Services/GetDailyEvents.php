<?php

declare(strict_types=1);

namespace App\Actions\Calendar\Services;

use App\Http\Resources\EventDayViewResource;
use App\Models\Event;
use App\Models\User;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Support\Carbon;

class GetDailyEvents
{
    private array $calendarView = [];

    public function __construct(private readonly User $user, private readonly string $date, private readonly string $timezone = 'Europe/Kyiv') {}

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
        $todayDate = Carbon::parse($this->date, $this->timezone)->startOfDay();
        $tomorrowDate = Carbon::parse($this->date, $this->timezone)->addDay()->startOfDay();
        /** @var ?Event $firstEvent */
        $firstEvent = $this->user->events()->orderBy('start_date_time')->first();
        /** @var ?Event $latestEvent */
        $latestEvent = $this->user->events()->orderBy('start_date_time', 'desc')->latest()->first();

        $startCalendarMonth = Carbon::parse($firstEvent->start_date_time ?? $this->date, 'UTC')->setTimezone($this->timezone)->startOfMonth();
        $endCalendarMonth = Carbon::parse($latestEvent->start_date_time ?? $this->date, 'UTC')->setTimezone($this->timezone)->endOfMonth();
        if ($todayDate->isAfter($endCalendarMonth)) {
            //            $endCalendarMonth = Carbon::parse($todayDate, $this->timezone)->endOfMonth();
            $endCalendarMonth = $todayDate->endOfMonth();
        }

        $dailyEvents = clone $this->user->events();
        $period = CarbonPeriod::create($startCalendarMonth, '1 month', $endCalendarMonth);

        return [
            'todayDate'    => $todayDate,
            'tomorrowDate' => $tomorrowDate,
            'months'       => $period->toArray(),
            'daysEvents'   => $dailyEvents->pluck('date')->unique()->toArray(),
        ];
    }

    /**
     * @return list
     */
    private function getDailyEvents(Carbon $todayDate, Carbon $tomorrowDate): array
    {
        $eventsCollection = $this->user->events()
            ->whereBetween('start_date_time', [$todayDate->setTimezone('UTC'), $tomorrowDate->setTimezone('UTC')])
            ->orderBy('start_date_time')
            ->get();

        $events = [];
        foreach ($eventsCollection as $dayEvent) {
            /** @var Event $dayEvent */
            $events[] = new EventDayViewResource($dayEvent, $this->timezone)->resolve();
        }

        return $events;
    }

    private function buildDailyCalendarView(array $months, Carbon $todayDate, array $daysEvents): void
    {
        foreach ($months as $month) {
            $startDate = Carbon::parse($month, $this->timezone)->startOfMonth()->startOfWeek();
            $endDate = Carbon::parse($month, $this->timezone)->endOfMonth()->endOfWeek();
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

    private function buildDayPayload(CarbonInterface $monthDate, Carbon $todayDate, array $daysEvents): array
    {
        $payload = ['date' => $monthDate->format('Y-m-d')];

        if ($monthDate->isSameMonth($todayDate)) {
            $payload['isCurrentMonth'] = true;
        }

        if ($monthDate->isSameDay($todayDate)) {
            $payload['isSelected'] = true;
        }

        if (Carbon::now($this->timezone)->isSameDay($monthDate)) {
            $payload['isToday'] = true;
        }

        if (in_array($monthDate->format('Y-m-d'), $daysEvents)) {
            $payload['hasEvent'] = true;
        }

        return $payload;
    }
}
