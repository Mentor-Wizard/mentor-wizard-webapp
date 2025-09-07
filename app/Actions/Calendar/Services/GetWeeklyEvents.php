<?php

declare(strict_types=1);

namespace App\Actions\Calendar\Services;

use App\Http\Resources\EventWeekViewResource;
use App\Models\Event;
use App\Models\User;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Support\Carbon;

class GetWeeklyEvents
{
    private array $calendarView = [];

    private array $events = [];

    public function __construct(private readonly User $user, private readonly string $date, private readonly string $timezone = 'Europe/Kyiv') {}

    public function execute(): array
    {
        $startDate = Carbon::parse($this->date, $this->timezone)->startOfWeek();
        $startUTCDate = $startDate->setTimezone('UTC');
        $endDate = Carbon::parse($this->date, $this->timezone)->endOfWeek();
        $endUTCDate = $endDate->setTimezone('UTC');
        $todayDate = Carbon::parse($this->date, $this->timezone);
        $userEvents = $this->user->events();
        $userEventsForCalendar = clone $userEvents;

        $eventsCollection = $userEvents->whereBetween('start_date_time',
            [$startUTCDate, $endUTCDate])->orderBy('start_date_time')->get();

        foreach ($eventsCollection as $dayEvent) {
            /** @var Event $dayEvent */
            $this->events[] = new EventWeekViewResource($dayEvent, $this->timezone)->resolve();
        }

        $daysEvents = $userEventsForCalendar->pluck('date')->unique()->toArray();
        $weekDays = CarbonPeriod::create($startDate, '1 day', $endDate);
        foreach ($weekDays as $weekDay) {
            $this->buildWeekPayload($weekDay, $daysEvents, $todayDate);
        }

        return [
            'events'       => $this->events,
            'calendarView' => $this->calendarView,
        ];
    }

    private function buildWeekPayload(CarbonInterface $weekDay, array $daysEvents, Carbon $todayDate): void
    {
        $payload = ['date' => $weekDay->format('Y-m-d')];
        if ($weekDay->isSameMonth($todayDate)) {
            $payload['isCurrentMonth'] = true;
        }

        if ($weekDay->isSameDay($todayDate)) {
            $payload['isSelected'] = true;
        }

        if (Carbon::now($this->timezone)->isSameDay($weekDay)) {
            $payload['isToday'] = true;
        }

        if (in_array($weekDay->format('Y-m-d'), $daysEvents)) {
            $payload['hasEvent'] = true;
        }

        $this->calendarView[] = $payload;
    }
}
