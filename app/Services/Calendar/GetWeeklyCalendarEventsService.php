<?php

declare(strict_types=1);

namespace App\Services\Calendar;

use App\Http\Resources\EventWeekViewResource;
use App\Models\CalendarEvent;
use App\Models\User;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Date;

class GetWeeklyCalendarEventsService
{
    private array $calendarView = [];

    private array $calendarEvents = [];

    public function __construct(private readonly User $user, private readonly CarbonInterface $date, private readonly string $timezone) {}

    /**
     * @return array<string, mixed[]>
     */
    public function getWeeklyCalendarEvents(): array
    {
        $startDate = $this->date->startOfWeek();
        $startUTCDate = (clone $startDate)->setTimezone(config('app.timezone'));
        $endDate = $this->date->endOfWeek();
        $endUTCDate = (clone $endDate)->setTimezone(config('app.timezone'));
        $todayDate = Date::parse($this->date, $this->timezone);
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

        /** @var Collection<int, CalendarEvent> $eventsForCalendar */
        $eventsForCalendar = $userEventsForCalendar->get();
        $eventsForCalendar->each(function (CalendarEvent $event): void {
            $event->date = Date::parse($event->start_date_time)->setTimezone($this->timezone)->format('Y-m-d');
        });

        $daysEvents = $eventsForCalendar->pluck('date')->unique()->toArray();
        $weekDays = CarbonPeriod::create($startDate, '1 day', $endDate);
        foreach ($weekDays as $weekDay) {
            $this->buildWeekPayload($weekDay, $daysEvents, $todayDate);
        }

        $this->calendarView = array_values($this->calendarView);

        return [
            'calendarEvents'       => $this->calendarEvents,
            'calendarView'         => $this->calendarView,
        ];
    }

    private function buildWeekPayload(CarbonInterface $weekDay, array $daysEvents, CarbonInterface $todayDate): void
    {

        $payload = ['date' => $weekDay->format('Y-m-d')];
        if ($weekDay->isSameMonth($todayDate)) {
            $payload['isCurrentMonth'] = true;
        }

        if ($weekDay->isSameDay($todayDate)) {
            $payload['isSelected'] = true;
        }

        if (Date::now($this->timezone)->isSameDay($weekDay)) {
            $payload['isToday'] = true;
        }

        if (in_array($weekDay->format('Y-m-d'), $daysEvents)) {
            $payload['hasEvent'] = true;
        }

        $this->calendarView[] = $payload;
    }
}
