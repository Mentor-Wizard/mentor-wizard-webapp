<?php

declare(strict_types=1);

namespace App\Services\Calendar;

use App\Http\Resources\EventWeekViewResource;
use App\Models\CalendarEvent;
use App\Models\User;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Support\Carbon;

class GetWeeklyEventsService
{
    private array $calendarView = [];

    private array $calendarEvents = [];

    public function __construct(private readonly User $user, private readonly string $date, private readonly string $timezone = 'UTC') {}

    public function execute(): array
    {
        $startDate = \Illuminate\Support\Facades\Date::parse($this->date, $this->timezone)->startOfWeek();
        $startUTCDate = (clone $startDate)->setTimezone('UTC');
        $endDate = \Illuminate\Support\Facades\Date::parse($this->date, $this->timezone)->endOfWeek();
        $endUTCDate = (clone $endDate)->setTimezone('UTC');
        $todayDate = \Illuminate\Support\Facades\Date::parse($this->date, $this->timezone);
        $userEvents = $this->user->calendarEvents()->with('calendarEventUsers');
        $userEventsForCalendar = clone $userEvents;

        $eventsCollection = $userEvents->whereBetween('start_date_time',
            [$startUTCDate, $endUTCDate])->orderBy('start_date_time')->get();

        foreach ($eventsCollection as $dayEvent) {
            /** @var CalendarEvent $dayEvent */
            $this->calendarEvents[] = new EventWeekViewResource($dayEvent, $this->timezone)
                ->additional(['user' => $this->user])
                ->resolve();
        }

        $userEventsForCalendar->tap(fn ($collection) => $collection->each(
            fn ($event): string => $event->date = \Illuminate\Support\Facades\Date::parse($event->start_date_time)->setTimezone($this->timezone)->format('Y-m-d')
        ));

        $daysEvents = $userEventsForCalendar->pluck('date')->unique()->toArray();
        $weekDays = CarbonPeriod::create($startDate, '1 day', $endDate);
        foreach ($weekDays as $weekDay) {
            $this->buildWeekPayload($weekDay, $daysEvents, $todayDate);
        }

        $this->calendarView = array_values($this->calendarView);

        return [
            'events'       => $this->calendarEvents,
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

        if (\Illuminate\Support\Facades\Date::now($this->timezone)->isSameDay($weekDay)) {
            $payload['isToday'] = true;
        }

        if (in_array($weekDay->format('Y-m-d'), $daysEvents)) {
            $payload['hasEvent'] = true;
        }

        $this->calendarView[] = $payload;
    }
}
