<?php

declare(strict_types=1);

namespace App\Actions\Calendar\Services;

use App\Http\Resources\EventMonthViewResource;
use App\Models\Event;
use App\Models\User;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

class GetMonthEvents
{
    private array $calendarView = [];

    public function __construct(private readonly User $user, private readonly string $date, private readonly string $timezone = 'Europe/Kyiv') {}

    public function execute(): array
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
        $startDate = Carbon::parse($this->date, $this->timezone)->startOfMonth()->startOfWeek();
        $endDate = Carbon::parse($this->date, $this->timezone)->endOfMonth()->endOfWeek();
        $period = CarbonPeriod::create($startDate, '1 day', $endDate);

        return [
            'startDate'  => $startDate,
            'endDate'    => $endDate,
            'monthDates' => $period->toArray(),
        ];
    }

    private function getFormattedEventsForPeriod(Carbon $startDate, Carbon $endDate): array
    {
        return $this->user->events()
            ->whereBetween('start_date_time', [$startDate, $endDate])
            ->orderBy('start_date_time')
            ->get()
            ->groupBy('date')
            ->map(fn (Collection $dateEvents): array => $this->formatDateEvents($dateEvents))
            ->toArray();
    }

    private function buildCalendarView(array $monthDates, array $events): array
    {
        foreach ($monthDates as $monthDate) {
            $dateKey = $monthDate->format('Y-m-d');
            $this->calendarView[] = Arr::has($events, $dateKey)
                ? $events[$dateKey]
                : ['date' => $dateKey, 'events' => []];
        }

        return $this->calendarView;
    }

    private function formatDateEvents(Collection $dateEvents): array
    {
        /** @var ?Event $firstEvent */
        $firstEvent = $dateEvents->first();
        $payload = [
            'date'   => $firstEvent->start_date_time->format('Y-m-d'),
            'events' => EventMonthViewResource::collection($dateEvents)->resolve(),
        ];

        $parsedDate = Carbon::parse($this->date, $this->timezone);
        $eventDate = $firstEvent->start_date_time;

        if ($parsedDate->isSameMonth($eventDate)) {
            $payload['isCurrentMonth'] = true;
        }

        if ($parsedDate->isSameDay($eventDate)) {
            $payload['isSelected'] = true;
        }

        if (Carbon::now()->isSameDay($eventDate)) {
            $payload['isToday'] = true;
        }

        return $payload;
    }

    private function hasEventsBeforeDate(Carbon $startDate): bool
    {
        return $this->user->events()->where('start_date_time', '<', $startDate)->exists();
    }

    private function hasEventsAfterDate(Carbon $endDate): bool
    {
        return $this->user->events()->where('start_date_time', '>', $endDate->endOfDay())->exists();
    }
}
