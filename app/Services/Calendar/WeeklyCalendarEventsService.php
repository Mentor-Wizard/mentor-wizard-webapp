<?php

declare(strict_types=1);

namespace App\Services\Calendar;

use App\Http\Resources\EventWeekViewResource;
use App\Models\CalendarEvent;
use App\Models\User;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Date;

class WeeklyCalendarEventsService
{
    private array $calendarView = [];

    private array $calendarEvents = [];

    public function __construct(
        private readonly User $user,
        private readonly CarbonInterface $date,
        private readonly string $timezone,
    ) {}

    /**
     * @return array<string, mixed[]>
     */
    public function getWeeklyCalendarEvents(): array
    {
        $startDate = $this->date->startOfWeek();
        // Convert to UTC for database queries
        $startUTCDate = $startDate->copy()->timezone('UTC');
        $endDate = $this->date->endOfWeek();
        // Convert to UTC for database queries
        $endUTCDate = $endDate->copy()->timezone('UTC');
        $todayDate = Date::parse($this->date, $this->timezone);

        // Single query to fetch all events for the week
        $eventsCollection = $this->user->calendarEvents()
            ->with('calendarEventUsers')
            ->whereBetween('start_date_time', [$startUTCDate, $endUTCDate])
            ->orderBy('start_date_time')
            ->get();

        // Build calendar events for display
        foreach ($eventsCollection as $dayEvent) {
            /** @var CalendarEvent $dayEvent */
            $this->calendarEvents[] = new EventWeekViewResource($dayEvent, $this->timezone)
                ->additional(['user' => $this->user])
                ->resolve();
        }

        // Reuse the same collection for calendar view
        $eventsCollection->each(function (CalendarEvent $event): void {
            $event->date = Date::parse($event->start_date_time)->timezone($this->timezone)->format('Y-m-d');
        });

        $daysEvents = $eventsCollection->pluck('date')->unique()->toArray();
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

    /**
     * @param  array<int|string, mixed>  $daysEvents
     */
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
