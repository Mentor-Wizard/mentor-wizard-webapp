<?php

declare(strict_types=1);

namespace App\Services\Calendar;

use App\DTO\Calendar\CalendarEventWeekViewData;
use App\Models\CalendarEvent;
use App\Models\User;
use App\Traits\Calendar\BuildsCalendarPayload;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Date;

class WeeklyCalendarEventsService
{
    use BuildsCalendarPayload;

    /** @var list<array<string, mixed>> */
    private array $calendarView = [];

    /** @var list<array<string, mixed>> */
    private array $calendarEvents = [];

    public function __construct(
        private readonly User $user,
        private readonly CarbonInterface $date,
        private readonly string $timezone,
    ) {}

    /**
     * @return array{
     *     calendarEvents: list<array<string, mixed>>,
     *     calendarView: list<array<string, mixed>>
     * }
     */
    public function getWeeklyCalendarEvents(): array
    {
        // Always work in UTC for database operations
        $utcDate = Date::parse($this->date)->timezone('UTC');
        $startUTCDate = $utcDate->copy()->startOfWeek();
        $endUTCDate = $utcDate->copy()->endOfWeek();

        // For calendar view, use user's timezone
        $startDate = Date::parse($this->date, $this->timezone)->startOfWeek();
        $endDate = Date::parse($this->date, $this->timezone)->endOfWeek();
        $todayDate = Date::parse($this->date, $this->timezone);

        // Single query to fetch all events for the week
        $eventsCollection = $this->user->calendarEvents()
            ->with('calendarEventUsers')
            ->whereBetween('start_date_time', [$startUTCDate, $endUTCDate])
            ->orderBy('start_date_time')
            ->get();
        /** @var Collection<int, CalendarEvent> $eventsCollection */
        // Build calendar events for display
        foreach ($eventsCollection as $dayEvent) {
            /** @var CalendarEvent $dayEvent */
            $this->calendarEvents[] = CalendarEventWeekViewData::fromModel($dayEvent,
                $this->timezone, $this->user)->toArray();
        }

        // Reuse the same collection for a calendar view
        $eventsCollection->each(function (CalendarEvent $event): void {
            $event->date = Date::parse($event->start_date_time)
                ->timezone($this->timezone)->format('Y-m-d');
        });

        $daysEvents = $eventsCollection->pluck('date')->unique()->toArray();
        $weekDays = CarbonPeriod::create($startDate, '1 day', $endDate);
        foreach ($weekDays as $weekDay) {
            $this->buildWeekPayload($weekDay, $daysEvents, $todayDate);
        }

        return [
            'calendarEvents'       => $this->calendarEvents,
            'calendarView'         => $this->calendarView,
        ];
    }

    /**
     * @param  array<int, string>  $daysEvents
     */
    private function buildWeekPayload(CarbonInterface $weekDay, array $daysEvents, CarbonInterface $todayDate): void
    {
        $this->calendarView[] = $this->buildDayPayload($weekDay, $todayDate, $daysEvents);
    }
}
